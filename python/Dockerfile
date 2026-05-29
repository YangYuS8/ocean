FROM docker.io/library/python:3.12-slim

COPY --from=ghcr.io/astral-sh/uv:0.8.15 /uv /uvx /usr/local/bin/

ARG DEBIAN_MIRROR=mirrors.ustc.edu.cn

RUN if [ -f /etc/apt/sources.list.d/debian.sources ]; then \
    sed -i "s|deb.debian.org|${DEBIAN_MIRROR}|g; s|security.debian.org|${DEBIAN_MIRROR}|g" /etc/apt/sources.list.d/debian.sources; \
  fi \
  && if [ -f /etc/apt/sources.list ]; then \
    sed -i "s|deb.debian.org|${DEBIAN_MIRROR}|g; s|security.debian.org|${DEBIAN_MIRROR}|g" /etc/apt/sources.list; \
  fi \
  && apt-get update && apt-get install -y --no-install-recommends \
    libglib2.0-0 \
    libgl1 \
    libxext6 \
    libsm6 \
    libxrender1 \
    libxcb1 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /workspace

ARG PIP_INDEX_URL=https://pypi.tuna.tsinghua.edu.cn/simple
ARG PIP_TRUSTED_HOST=pypi.tuna.tsinghua.edu.cn
ENV UV_INDEX_URL=${PIP_INDEX_URL}
ENV UV_INSECURE_HOST=${PIP_TRUSTED_HOST}
ENV UV_LINK_MODE=copy
ENV UV_CACHE_DIR=/root/.cache/uv
ENV VIRTUAL_ENV=/workspace/.venv
ENV PATH="/workspace/.venv/bin:${PATH}"

COPY pyproject.toml uv.lock /workspace/
RUN uv sync --frozen --no-dev

COPY ocean_python /workspace/ocean_python
COPY models /workspace/models

CMD ["/workspace/.venv/bin/python", "-m", "ocean_python.yolo_worker"]
