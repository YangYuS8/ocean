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

## SPA target static hosting example

When the React/Vite line becomes the active workspace frontend, the intended serving pattern is:

1. build `frontend-spa/` into `frontend-spa/dist`
2. mount or copy the dist output into an Nginx image or host path
3. serve `/` through SPA fallback to `index.html`
4. reverse proxy `/api/` to the Laravel / PHP entry point

The sample files added for this transition are:

- `nginx/spa-target.conf.example`
- `frontend-spa/Dockerfile`
- `docker-compose.spa.example.yml`

They are examples only and intentionally do not replace the current default deployment.

## Long-term direction explicitly not recommended

The project should not continue to treat `Nuxt SSR / Nitro` as the long-term deployment mainline, because:

- it offers limited value for an internal management workspace
- a long-running Node runtime increases deployment and troubleshooting complexity
- the separation of docs, workspace UI, and API is cleaner with SPA + Laravel
