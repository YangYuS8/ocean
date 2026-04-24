# Docker Startup Sequencing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prevent the public Nginx entrypoint from serving traffic before the Nuxt frontend is ready after `docker compose up -d --build`.

**Architecture:** Install frontend dependencies during image build, use an HTTP health check to represent frontend readiness, and gate Nginx startup on that health signal. Keep the change minimal by touching only the frontend image, Compose service wiring, and the setup documentation.

**Tech Stack:** Docker Compose, Dockerfile, Node 24, pnpm 10, Nuxt 4, Nginx

---

## File Structure

- Modify: `frontend/Dockerfile` to move `pnpm install` into the image build and make runtime command start only the dev server.
- Modify: `docker-compose.yml` to add a frontend health check and gate `nginx` on `service_healthy`.
- Modify: `README.md` to document the rebuilt-image workflow for frontend dependency changes.

### Task 1: Build Frontend Dependencies Into The Image

**Files:**
- Modify: `frontend/Dockerfile`

- [ ] **Step 1: Update the Dockerfile dependency copy order**

```Dockerfile
FROM docker.io/library/node:24-alpine

WORKDIR /app

ARG NPM_REGISTRY=https://registry.npmmirror.com
ENV NPM_CONFIG_REGISTRY=${NPM_REGISTRY}
ENV PNPM_HOME=/pnpm
ENV PATH=${PNPM_HOME}:${PATH}
ENV HOST=0.0.0.0
ENV PORT=3000

RUN corepack enable

COPY package.json pnpm-lock.yaml ./
RUN corepack prepare pnpm@10.30.3 --activate && pnpm install --frozen-lockfile --prefer-offline

COPY . .

CMD ["sh", "-c", "pnpm dev --host ${HOST} --port ${PORT}"]
```

- [ ] **Step 2: Rebuild the frontend image**

Run: `docker compose build frontend`
Expected: build completes successfully and the install step runs during image build.

### Task 2: Represent Frontend Readiness In Compose

**Files:**
- Modify: `docker-compose.yml`

- [ ] **Step 1: Add a frontend health check**

```yaml
  frontend:
    healthcheck:
      test: ["CMD", "wget", "-qO-", "http://127.0.0.1:3000/"]
      interval: 5s
      timeout: 3s
      retries: 20
      start_period: 10s
```

- [ ] **Step 2: Gate nginx on frontend health**

```yaml
  nginx:
    depends_on:
      frontend:
        condition: service_healthy
      php:
        condition: service_started
```

- [ ] **Step 3: Recreate the affected services**

Run: `docker compose up -d --build frontend nginx`
Expected: frontend starts first, nginx waits until frontend is healthy.

### Task 3: Document The New Developer Workflow

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Add a note near the frontend startup instructions**

```md
前端依赖现在在镜像构建阶段安装。若修改了 `frontend/package.json` 或 `frontend/pnpm-lock.yaml`，请重新执行：

```bash
docker compose up -d --build frontend
```
```

- [ ] **Step 2: Verify the note matches the actual workflow**

Run: `docker compose build frontend`
Expected: dependency installation happens during build, not when the frontend container starts.

### Task 4: Verify Startup Sequencing End To End

**Files:**
- Modify: `frontend/Dockerfile`
- Modify: `docker-compose.yml`
- Modify: `README.md`

- [ ] **Step 1: Start the stack with rebuilt frontend and nginx**

Run: `docker compose up -d --build frontend nginx`
Expected: command completes and services are recreated.

- [ ] **Step 2: Confirm Compose reports frontend health**

Run: `docker compose ps`
Expected: `frontend` shows `healthy` before or by the time `nginx` is fully running.

- [ ] **Step 3: Verify the public entrypoint succeeds**

Run: `curl -I http://127.0.0.1:8080`
Expected: `HTTP/1.1 200 OK`

- [ ] **Step 4: Check logs for missing initial upstream refusal**

Run: `docker compose logs --tail=100 nginx frontend`
Expected: frontend logs show direct startup into `pnpm dev`, and nginx logs do not include `connect() failed (111: Connection refused)` for the startup test request.
