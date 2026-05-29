---
title: System Architecture
---

# System Architecture

## High-level topology

```text
Browser
  -> Nginx
      -> React SPA static assets (target mainline)
      -> Nuxt frontend runtime (transitional current flow)
      -> Laravel API (/api)
          -> MariaDB
          -> Redis
          -> analysis-worker
```

## Transition status in v1.1

The platform is intentionally in a frontend transition period:

- `frontend/` remains the active Nuxt/Vue implementation for the current running flow
- `frontend-spa/` is introduced in v1.1.0 as the target React + TypeScript + Vite baseline
- Laravel keeps the stable `/api` contract so both frontend lines can coexist during migration
- Nginx remains the boundary that can switch between the transitional Nuxt path and the target static SPA path

## Component responsibilities

### Laravel API

Laravel is the center of the business system. It is responsible for:

- exposing `/api` endpoints
- enforcing business rules and state transitions
- owning migrations and seeders
- serving as the future integration point for authentication, authorization, auditing, and queue orchestration

### React / TypeScript SPA

The business frontend should exist as a standalone SPA that:

- consumes Laravel APIs
- hosts the task, sample, result, exception, and analysis workflows
- ships as static assets served by Nginx
- reads its API root from `VITE_API_BASE`, defaulting to `/api`

In v1.1.0, this SPA is delivered as a foundation skeleton only. It proves the target runtime, API boundary, and static deployment shape without replacing the existing Nuxt flow yet.

### Nuxt / Vue transitional frontend

The existing Nuxt frontend remains in place during the transition because it still carries the current working flow. It should now be treated as a transitional implementation rather than the long-term business frontend mainline.

### Analysis Worker

The analysis worker is implemented in Python and reserved for analysis and algorithm execution, including:

- image processing
- automatic suggestion generation
- model inference
- background execution for `analysis_jobs`

### MariaDB

MariaDB remains the core transactional store for:

- inspection tasks
- samples
- sample results
- exceptions
- analysis jobs
- users and roles

### Redis

Redis remains the async boundary used to:

- decouple Laravel from analysis workers
- support queueing, retries, and later orchestration work

### Nginx

Nginx remains the unified entry point that:

- proxies Laravel API traffic
- serves SPA build output
- hides internal service topology from end users

## Core business chain

```text
inspection_tasks
  -> samples
      -> sample_results
      -> exceptions
      -> analysis_jobs
```

## Analysis job flow

```text
User action
  -> Laravel creates analysis_jobs(queued)
  -> Laravel pushes job id to Redis list ANALYSIS_JOB_REDIS_QUEUE
  -> analysis-worker consumes the Redis queue
  -> analysis-worker calls Laravel to mark running / succeeded / failed
```

Redis carries only the worker handoff payload. MariaDB remains the durable source of truth for job status, parameters, summaries, failures, retries, and historical records. Retrying a failed job creates a new queued database row and a new Redis queue entry instead of reviving the old failed record.

## Design principles

1. **Backend-centric rules**: state machines and validation stay in Laravel.
2. **Separated concerns**: the frontend consumes APIs instead of owning SSR responsibilities.
3. **Isolated async execution**: the analysis worker does not own the main transactional workflow.
4. **Stable deployment path**: Nginx + Docker Compose stays the main delivery model.
5. **Controlled frontend transition**: v1.1 establishes coexistence and deployment boundaries before v1.2 moves core workspace delivery onto the SPA.
