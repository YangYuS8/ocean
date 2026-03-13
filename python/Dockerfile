FROM docker.io/library/python:3.12-slim

COPY --from=ghcr.io/astral-sh/uv:0.8.15 /uv /uvx /usr/local/bin/

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

CMD ["sh", "-lc", "test -x /workspace/.venv/bin/python || uv sync --frozen --no-dev; uv sync --frozen --no-dev && sleep infinity"]
