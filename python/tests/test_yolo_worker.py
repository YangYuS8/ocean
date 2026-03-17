import tempfile
import unittest
from pathlib import Path

from ocean_python.yolo_worker import (
    build_suggestion_payload,
    process_job,
    run_worker_iteration,
)


class FakeApiClient:
    def __init__(self, job):
        self.job = job
        self.started = []
        self.succeeded = []
        self.failed = []

    def get_job(self, job_id):
        assert job_id == self.job["id"]
        return self.job

    def start_job(self, job_id):
        self.started.append(job_id)

    def succeed_job(self, job_id, result_summary, suggestion):
        self.succeeded.append((job_id, result_summary, suggestion))

    def fail_job(self, job_id, error_message):
        self.failed.append((job_id, error_message))


class FakeDetector:
    def __init__(self, detections=None, error=None):
        self.detections = detections or []
        self.error = error
        self.calls = []

    def detect(self, image_path):
        self.calls.append(image_path)
        if self.error:
            raise self.error
        return self.detections


class FlakyQueueApiClient:
    def __init__(self, errors=None, jobs=None):
        self.errors = list(errors or [])
        self.jobs = list(jobs or [])
        self.list_calls = 0

    def list_queued_jobs(self):
        self.list_calls += 1
        if self.errors:
            raise self.errors.pop(0)
        return self.jobs


class YoloWorkerTests(unittest.TestCase):
    def test_build_suggestion_payload_with_findings(self):
        suggestion = build_suggestion_payload(
            [
                {"label": "scallop", "confidence": 0.91},
                {"label": "scallop", "confidence": 0.82},
                {"label": "starfish", "confidence": 0.77},
            ]
        )

        self.assertTrue(suggestion["has_findings"])
        self.assertEqual({"scallop": 2, "starfish": 1}, suggestion["counts"])
        self.assertEqual(0.91, suggestion["confidence_summary"]["top_score"])
        self.assertIn("scallop x2", suggestion["result_summary"])

    def test_build_suggestion_payload_without_findings(self):
        suggestion = build_suggestion_payload([])

        self.assertFalse(suggestion["has_findings"])
        self.assertEqual({}, suggestion["counts"])
        self.assertEqual("未检测到明确目标", suggestion["result_summary"])

    def test_process_job_starts_detector_and_writes_success(self):
        with tempfile.TemporaryDirectory() as temp_dir:
            image_path = Path(temp_dir) / "sample.jpg"
            image_path.write_bytes(b"test-image")

            job = {
                "id": 101,
                "params": {
                    "main_image_path": "sample.jpg",
                },
            }
            api_client = FakeApiClient(job)
            detector = FakeDetector(
                [
                    {"label": "scallop", "confidence": 0.91},
                ]
            )

            process_job(
                101,
                api_client=api_client,
                detector=detector,
                storage_root=Path(temp_dir),
            )

            self.assertEqual([101], api_client.started)
            self.assertEqual([str(image_path)], detector.calls)
            self.assertEqual(1, len(api_client.succeeded))
            self.assertEqual([], api_client.failed)

    def test_process_job_reports_failure(self):
        with tempfile.TemporaryDirectory() as temp_dir:
            image_path = Path(temp_dir) / "sample.jpg"
            image_path.write_bytes(b"test-image")

            job = {
                "id": 102,
                "params": {
                    "main_image_path": "sample.jpg",
                },
            }
            api_client = FakeApiClient(job)
            detector = FakeDetector(error=RuntimeError("model unavailable"))

            process_job(
                102,
                api_client=api_client,
                detector=detector,
                storage_root=Path(temp_dir),
            )

            self.assertEqual([102], api_client.started)
            self.assertEqual([], api_client.succeeded)
            self.assertEqual([(102, "model unavailable")], api_client.failed)

    def test_worker_iteration_survives_transient_queue_fetch_error(self):
        api_client = FlakyQueueApiClient(errors=[RuntimeError("nginx unavailable")])
        detector = FakeDetector()

        processed = run_worker_iteration(
            api_client=api_client,
            detector=detector,
            storage_root=Path("."),
        )

        self.assertEqual(0, processed)
        self.assertEqual(1, api_client.list_calls)
        self.assertEqual([], detector.calls)


if __name__ == "__main__":
    unittest.main()
