from __future__ import annotations

import json
import logging
import os
import time
from pathlib import Path
from typing import Any
from urllib import error, parse, request


logger = logging.getLogger(__name__)

DEFAULT_REDIS_QUEUE = "ocean:analysis-jobs:queued"


def build_suggestion_payload(detections: list[dict[str, Any]]) -> dict[str, Any]:
    counts: dict[str, int] = {}
    top_score = 0.0

    for detection in detections:
        label = str(detection["label"])
        counts[label] = counts.get(label, 0) + 1
        top_score = max(top_score, float(detection.get("confidence", 0.0)))

    has_findings = len(detections) > 0
    result_summary = "未检测到明确目标"
    if counts:
        result_summary = ", ".join(
            f"{label} x{count}" for label, count in counts.items()
        )

    return {
        "has_findings": has_findings,
        "counts": counts,
        "confidence_summary": {
            "top_score": round(top_score, 4) if has_findings else None,
        },
        "result_summary": result_summary,
    }


def process_job(
    job_id: int, *, api_client: Any, detector: Any, storage_root: Path
) -> bool:
    job = api_client.get_job(job_id)
    params = job.get("params") or {}
    job_type = job.get("job_type")

    api_client.start_job(job_id)

    try:
        if job_type == "quality_assessment":
            suggestion = {
                "has_findings": False,
                "counts": {},
                "confidence_summary": {"top_score": None},
                "result_summary": "质量评估任务已完成，未发现自动规则异常",
                "params": params,
            }
            api_client.succeed_job(job_id, suggestion["result_summary"], suggestion)
            return True

        image_path = storage_root / params["main_image_path"]
        detections = detector.detect(str(image_path))
        suggestion = build_suggestion_payload(detections)
        api_client.succeed_job(job_id, suggestion["result_summary"], suggestion)
        return True
    except Exception as exc:  # noqa: BLE001
        api_client.fail_job(job_id, str(exc))
        return False


class LaravelApiClient:
    def __init__(self, base_url: str) -> None:
        self.base_url = base_url.rstrip("/")

    def get_job(self, job_id: int) -> dict[str, Any]:
        return self._json_request("GET", f"/api/analysis-jobs/{job_id}")["data"]

    def list_queued_jobs(self) -> list[dict[str, Any]]:
        query = parse.urlencode(
            {"status": "queued", "page_size": 50}
        )
        return self._json_request("GET", f"/api/analysis-jobs?{query}")["data"]

    def start_job(self, job_id: int) -> None:
        self._json_request("POST", f"/api/analysis-jobs/{job_id}/start")

    def succeed_job(
        self, job_id: int, result_summary: str, suggestion: dict[str, Any]
    ) -> None:
        self._json_request(
            "POST",
            f"/api/analysis-jobs/{job_id}/succeed",
            {
                "result_summary": result_summary,
                "suggestion": suggestion,
            },
        )

    def fail_job(self, job_id: int, error_message: str) -> None:
        self._json_request(
            "POST",
            f"/api/analysis-jobs/{job_id}/fail",
            {
                "error_message": error_message,
            },
        )

    def _json_request(
        self, method: str, path: str, payload: dict[str, Any] | None = None
    ) -> dict[str, Any]:
        data = None
        headers = {"Accept": "application/json", "X-Ocean-Worker": "ocean-analysis-worker"}

        if payload is not None:
            data = json.dumps(payload).encode("utf-8")
            headers["Content-Type"] = "application/json"

        req = request.Request(
            f"{self.base_url}{path}", data=data, method=method, headers=headers
        )

        try:
            with request.urlopen(req, timeout=30) as response:
                return json.loads(response.read().decode("utf-8"))
        except error.HTTPError as exc:
            body = exc.read().decode("utf-8")
            raise RuntimeError(body or f"API request failed: {exc.code}") from exc


class RedisJobQueue:
    def __init__(self, *, host: str, port: int, queue_name: str, timeout: int = 1) -> None:
        try:
            import redis
        except ImportError as exc:  # pragma: no cover - depends on runtime env
            raise RuntimeError("redis package is not installed in python environment") from exc

        self.queue_name = queue_name
        self.timeout = timeout
        self.client = redis.Redis(host=host, port=port, decode_responses=True)

    def pop_job(self) -> dict[str, Any] | None:
        item = self.client.blpop(self.queue_name, timeout=self.timeout)
        if item is None:
            return None

        _queue, payload = item
        decoded = json.loads(payload)

        if not isinstance(decoded, dict) or "id" not in decoded:
            raise RuntimeError("invalid analysis job queue payload")

        return decoded


class UltralyticsDetector:
    def __init__(self, model_path: str) -> None:
        self.model_path = model_path
        self.model = None

    def detect(self, image_path: str) -> list[dict[str, Any]]:
        try:
            from ultralytics import YOLO
        except ImportError as exc:  # pragma: no cover - depends on runtime env
            raise RuntimeError(
                "ultralytics is not installed in python environment"
            ) from exc

        if self.model is None:
            self.model = YOLO(self.model_path)

        results = self.model(image_path, verbose=False)
        detections: list[dict[str, Any]] = []

        for result in results:
            names = result.names
            for box in result.boxes:
                cls_index = int(box.cls.item())
                detections.append(
                    {
                        "label": names.get(cls_index, str(cls_index)),
                        "confidence": float(box.conf.item()),
                    }
                )

        return detections


def run_worker_iteration(
    *,
    api_client: Any,
    detector: Any,
    storage_root: Path,
    job_queue: Any | None = None,
) -> int:
    if job_queue is not None:
        try:
            queued_job = job_queue.pop_job()
        except Exception as exc:  # noqa: BLE001
            logger.warning("failed to fetch job from redis queue: %s", exc)
            return 0

        if queued_job is None:
            return 0

        process_job(
            int(queued_job["id"]),
            api_client=api_client,
            detector=detector,
            storage_root=storage_root,
        )
        return 1

    try:
        jobs = api_client.list_queued_jobs()
    except Exception as exc:  # noqa: BLE001
        logger.warning("failed to fetch queued jobs: %s", exc)
        return 0

    processed = 0

    for job in jobs:
        process_job(
            int(job["id"]),
            api_client=api_client,
            detector=detector,
            storage_root=storage_root,
        )
        processed += 1

    return processed


def run_worker_loop() -> None:  # pragma: no cover - exercised in container
    base_url = os.environ.get("OCEAN_API_BASE", "http://nginx")
    model_path = os.environ.get("OCEAN_YOLO_MODEL_PATH")
    storage_root = Path(
        os.environ.get("OCEAN_STORAGE_ROOT", "/workspace/backend-storage")
    )
    poll_seconds = float(os.environ.get("OCEAN_WORKER_POLL_SECONDS", "3"))
    redis_host = os.environ.get("REDIS_HOST")
    redis_port = int(os.environ.get("REDIS_PORT", "6379"))
    redis_queue_name = os.environ.get("ANALYSIS_JOB_REDIS_QUEUE", DEFAULT_REDIS_QUEUE)

    if not model_path:
        raise RuntimeError("OCEAN_YOLO_MODEL_PATH is required")

    api_client = LaravelApiClient(base_url)
    detector = UltralyticsDetector(model_path)
    job_queue = None

    if redis_host:
        job_queue = RedisJobQueue(
            host=redis_host,
            port=redis_port,
            queue_name=redis_queue_name,
            timeout=max(1, int(poll_seconds)),
        )

    while True:
        processed = run_worker_iteration(
            api_client=api_client,
            detector=detector,
            storage_root=storage_root,
            job_queue=job_queue,
        )
        if processed == 0:
            time.sleep(poll_seconds)


if __name__ == "__main__":  # pragma: no cover - exercised in container
    run_worker_loop()
