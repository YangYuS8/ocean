---
title: Deployment and Operations
---

# Deployment and Operations

## Recommended deployment line

The long-term deployment topology should continue to converge around:

```text
Nginx
  -> Laravel API
  -> React SPA static assets
MariaDB
Redis
Python Worker
Docker Compose
```

## v1.1 frontend transition deployment note

The repository now contains two frontend lines with different roles:

- `frontend/`: current Nuxt/Vue runtime kept for the active flow
- `frontend-spa/`: target React/Vite SPA baseline for static build output

v1.1.0 does **not** switch the default deployment to the SPA yet. Instead, it delivers the deployable foundation required for the later cutover:

- a standalone SPA build that outputs `frontend-spa/dist`
- an example Nginx config for serving that directory
- an example Compose override / Dockerfile path for static SPA builds

This keeps the existing `docker-compose.yml` and current runtime behavior intact while making the target path concrete.

## Laravel runtime requirements

- Laravel remains the unified backend entry point
- database initialization uses migrations and seeders
- the old lightweight PHP implementation remains only as historical reference in `backend/legacy-lightweight/`

## Database initialization

After starting services, the recommended command is:

```bash
docker compose exec php php /var/www/html/artisan migrate --seed --force
```

To reset the database:

```bash
docker compose exec php php /var/www/html/artisan migrate:fresh --seed --force
```

## Python and Redis boundary

Current operating assumptions:

- `analysis_jobs` are persisted in MariaDB
- Redis list `ANALYSIS_JOB_REDIS_QUEUE` is the async worker handoff boundary
- Python Worker processes analysis workloads
- the default YOLO model path is `python/models/uprc2018/best.pt`

Operationally, this should be understood as:

1. Laravel creates and queries jobs
2. Laravel pushes queued job IDs to Redis after durable database creation
3. Python Worker consumes Redis, executes supported jobs, and reports results back through Laravel APIs

The default queue name is:

```bash
REDIS_PREFIX=
ANALYSIS_JOB_REDIS_QUEUE=ocean:analysis-jobs:queued
```

`REDIS_PREFIX` should remain empty for this worker handoff path so Laravel and the Python worker read and write the same Redis list name.

If Redis is temporarily unavailable during job creation, the durable database row is still preserved. Operators can retry failed jobs or requeue jobs once Redis is healthy.

## Common validation commands

### Governance actor context

```bash
curl -s -X POST http://127.0.0.1:8080/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"username":"admin","password":"password"}'
curl -s -H 'X-Ocean-Actor-Id: 3' http://127.0.0.1:8080/api/governance/me
curl -s http://127.0.0.1:8080/api/governance/roles
```

For v1.4.0, the SPA authenticates with `POST /api/auth/login` and sends `Authorization: Bearer <token>` for protected mutation routes. `X-Ocean-Actor-Id` is an internal identity-injection bridge, not a public authentication mechanism. It remains available for non-SPA tooling during the transition, but protected user mutation routes require bearer tokens.

Python Worker callbacks use an internal bridge header while the project waits for a real worker credential:

```bash
curl -s -H 'X-Ocean-Worker: ocean-python-worker' http://127.0.0.1:8080/api/analysis-jobs
```

### Audit events

```bash
curl -s http://127.0.0.1:8080/api/audit-events?page_size=20
curl -s 'http://127.0.0.1:8080/api/audit-events?resource_type=analysis_job'
```

Use audit events to verify high-value actions such as task start/submit, sample result creation, exception resolution, and analysis job lifecycle transitions.

### Settings and user management

After logging in as `admin`, capture the bearer token and validate the v1.4.1 governance pages through the API:

```bash
TOKEN="$(
  curl -s -X POST http://127.0.0.1:8080/api/auth/login \
    -H 'Content-Type: application/json' \
    -d '{"username":"admin","password":"password"}' \
  | python3 -c 'import json,sys; print(json.load(sys.stdin)["data"]["token"])'
)"

curl -s -H "Authorization: Bearer $TOKEN" http://127.0.0.1:8080/api/profile
curl -s -H "Authorization: Bearer $TOKEN" http://127.0.0.1:8080/api/settings
curl -s -H "Authorization: Bearer $TOKEN" 'http://127.0.0.1:8080/api/users?page_size=20'

curl -s -X PATCH http://127.0.0.1:8080/api/settings \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"language":"zh-Hans","display_density":"comfortable","default_workspace_tab":"settings"}'
```

To verify audit coverage for user/profile/settings changes:

```bash
curl -s 'http://127.0.0.1:8080/api/audit-events?resource_type=user&page_size=20'
```

### Dashboard summary

```bash
curl -s http://127.0.0.1:8080/api/dashboard/summary
```

### Inspection tasks

```bash
curl -s http://127.0.0.1:8080/api/inspection-tasks
```

### Laravel API route list

```bash
docker exec ocean-php php /var/www/html/artisan route:list --path=api
```

### Migration status

```bash
docker exec ocean-php php /var/www/html/artisan migrate:status
```

### Analysis queue depth

```bash
docker exec ocean-redis redis-cli LLEN ocean:analysis-jobs:queued
```

## Documentation site deployment

The documentation site lives in `website/`, uses Docusaurus, and is deployed independently to GitHub Pages.

The delivery rules are:

- documentation build stays separate from business service builds
- documentation can be published independently
- English is the default locale and Simplified Chinese remains available through i18n

## Default SPA static hosting

The default Compose path now serves the React/Vite workspace frontend:

1. `frontend` builds from `frontend-spa/Dockerfile`
2. the SPA image serves static assets on port `80` with history fallback to `index.html`
3. top-level Nginx proxies `/` to the SPA container
4. top-level Nginx routes `/api/` to the Laravel / PHP entry point

The relevant files are:

- `nginx/default.conf`
- `frontend-spa/Dockerfile`
- `frontend-spa/nginx.conf`
- `docker-compose.yml`

The earlier `frontend/` Nuxt implementation remains in the repository as a reference implementation, but it is no longer the default Compose/Nginx runtime.

## v1.4.2 production image direction

The next deployment hardening step is to package the main web application as one production image while keeping infrastructure and analysis workloads isolated.

Recommended production topology:

```text
app
  - Nginx
  - PHP-FPM
  - Laravel API
  - built React/Vite SPA

db
redis
analysis-worker
```

The intended `app` image should replace the current production need for separate `frontend`, `nginx`, and `php` containers. It should not include MariaDB, Redis, or the analysis worker process.

The target image should be built with a multi-stage flow:

1. build `frontend-spa/` with `pnpm install --frozen-lockfile` and `pnpm run build`
2. install backend Composer dependencies with `--no-dev --optimize-autoloader`
3. copy Laravel source, vendor dependencies, and SPA build output into a runtime image
4. run Nginx and PHP-FPM together through an entrypoint or supervisor

Production Nginx should serve SPA files directly with history fallback and route `/api/` to Laravel `public/index.php` through local PHP-FPM. Runtime configuration must come from environment variables; do not bake `.env` secrets into the image.

The current `python` service name should be replaced in the production path with `analysis-worker`, because the service role is asynchronous analysis execution, image/model inference, and result write-back. Python remains the implementation language, not the deployment-facing product role.

Storage needs special handling: Laravel public/uploads storage should remain persistent and, when image analysis jobs require local files, should be mounted into `analysis-worker` with the same semantics currently used by `OCEAN_STORAGE_ROOT`.

Migrations should remain an explicit deployment step, for example:

```bash
docker compose run --rm app php artisan migrate --force
```

Do not silently run migrations on every web container boot unless the release process explicitly adopts that policy.

## Long-term direction explicitly not recommended

The project should not continue to treat `Nuxt SSR / Nitro` as the long-term deployment mainline, because:

- it offers limited value for an internal management workspace
- a long-running Node runtime increases deployment and troubleshooting complexity
- the separation of docs, workspace UI, and API is cleaner with SPA + Laravel
