# Docker Startup Sequencing Design

## Goal

Eliminate the initial `502 Bad Gateway` window after `docker compose up -d --build` by ensuring the frontend is ready before the public Nginx entrypoint starts serving browser traffic.

## Current Problem

- The frontend container currently starts with `pnpm install && pnpm dev`.
- Dependency installation happens on the critical startup path and can take multiple minutes when the registry is slow.
- Nginx starts before the frontend is listening on port `3000`.
- During that gap, requests to `http://127.0.0.1:8080/` fail with `502 Bad Gateway`.

## Design

### 1. Move dependency installation to image build time

The frontend image will install dependencies during `docker build` instead of at container startup.

Expected behavior:
- `docker compose up -d --build` spends time resolving packages while building the image.
- Once the frontend container starts, it immediately runs `pnpm dev` instead of reinstalling dependencies.

This keeps the startup path short and removes network-sensitive package installation from the runtime readiness path.

### 2. Add a frontend health check

The `frontend` service will expose readiness through an HTTP health check against `http://127.0.0.1:3000/`.

Expected behavior:
- The service is marked healthy only after Nuxt is listening and serving responses.
- Compose status becomes a direct signal for frontend readiness.

### 3. Make Nginx wait for frontend readiness

The `nginx` service will depend on the frontend with `condition: service_healthy`.

Expected behavior:
- Nginx does not start until the frontend health check passes.
- The public entrypoint on port `8080` is not exposed before the upstream exists.

## Files In Scope

- `frontend/Dockerfile`
- `docker-compose.yml`
- `README.md`

## Out Of Scope

- Production deployment changes
- Reworking the development server away from `nuxt dev`
- Solving later runtime restarts of the frontend after Compose startup
- Registry mirror or package manager policy changes beyond what is needed for this startup fix

## Error Handling And Failure Mode

- If the frontend fails to start, it remains `unhealthy` instead of allowing Nginx to proxy to a closed port.
- Operators can detect the issue via `docker compose ps` and frontend logs, rather than discovering it only after a browser-facing `502`.

## Verification

Successful verification requires all of the following:

1. `docker compose up -d --build frontend nginx` completes without manual waiting logic.
2. `docker compose ps` shows the frontend becoming healthy.
3. `curl -I http://127.0.0.1:8080` returns `200 OK` after startup.
4. Nginx logs no longer show `connect() failed (111: Connection refused)` for the initial page request during a normal startup sequence.

## Tradeoff

Installing dependencies at build time means dependency changes require rebuilding the frontend image. This is acceptable because it removes a fragile runtime step and matches the user's priority of making Compose startup predictable.
