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
- Redis remains the async boundary
- Python Worker processes analysis workloads
- the default YOLO model path is `python/models/uprc2018/best.pt`

Operationally, this should be understood as:

1. Laravel creates and queries jobs
2. Redis preserves decoupling and queue semantics
3. Python Worker executes and reports results back

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

## Documentation site deployment

The documentation site lives in `website/`, uses Docusaurus, and is deployed independently to GitHub Pages.

The delivery rules are:

- documentation build stays separate from business service builds
- documentation can be published independently
- English is the default locale and Simplified Chinese remains available through i18n

## Long-term direction explicitly not recommended

The project should not continue to treat `Nuxt SSR / Nitro` as the long-term deployment mainline, because:

- it offers limited value for an internal management workspace
- a long-running Node runtime increases deployment and troubleshooting complexity
- the separation of docs, workspace UI, and API is cleaner with SPA + Laravel
