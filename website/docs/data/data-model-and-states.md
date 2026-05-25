---
title: Data Model and State Flows
---

# Data Model and State Flows

This page consolidates the old data-model draft, state-transition draft, SQL planning notes, and related OpenSpec constraints.

## Core tables

The P0 phase uses eight core tables:

- `users`
- `roles`
- `user_roles`
- `inspection_tasks`
- `samples`
- `sample_results`
- `exceptions`
- `analysis_jobs`

## Data relationships

```text
users ----< inspection_tasks ----< samples ----< sample_results
  |                |                    |            \
  |                |                    |             \-> exceptions
  |                |                    |
  |                |                    \-> analysis_jobs
  |
  \----< user_roles >---- roles
```

`exceptions` uses `resource_type + resource_id` to reference tasks, samples, or result records.

## Database implementation baseline

- target database: `MariaDB`
- charset: `utf8mb4`
- primary key strategy: `BIGINT UNSIGNED AUTO_INCREMENT`
- time fields: `DATETIME`
- state fields: `VARCHAR(20)`
- JSON fields: used for result content and analysis job parameters / summaries

## State machines

### inspection_tasks

States:

- `assigned`
- `in_progress`
- `submitted`
- `completed`
- `cancelled`

```text
assigned -> in_progress -> submitted -> completed
    \                             \
     \----------------------------> cancelled
```

Current explicit P0 actions:

- `start`: `assigned -> in_progress`
- `submit`: `in_progress -> submitted`

### samples

States:

- `registered`
- `received`
- `testing`
- `reviewed`
- `archived`
- `invalid`

```text
registered -> received -> testing -> reviewed -> archived
     \            \          \           \
      \            \          \-----------> invalid
       \------------\--------------------> invalid
```

Notes:

- `POST /api/samples` starts at `registered`
- sample status is advanced by backend service rules in the current P0 phase
- explicit v1.0.0 P0 rule: creating a sample result moves `registered` or `received` to `testing`
- explicit v1.0.0 P0 rule: `invalid` and `archived` samples cannot accept new result records and must return `409 INVALID_STATE`

### sample_results

States:

- `draft`
- `submitted`
- `approved`
- `rejected`

```text
draft -> submitted -> approved
             \
              \-> rejected
```

Notes:

- result creation starts at `draft`
- `status` drives the main current workflow
- `review_status` remains reserved for later expansion

### exceptions

States:

- `open`
- `resolved`
- `dismissed`

```text
open -> resolved
  \
   \-> dismissed
```

Current P0 action:

- `resolve`: `open -> resolved`

### analysis_jobs

States:

- `queued`
- `running`
- `succeeded`
- `failed`
- `cancelled`

```text
queued -> running -> succeeded
   |         \
   |          \-> failed
   \
    \-> cancelled
```

Key retry rule:

- retrying a failed job must not revive the original record
- the original `failed` record must remain in history
- a retry creates a new `queued` record
- each new `queued` record is handed to Python workers through the Redis queue configured by `ANALYSIS_JOB_REDIS_QUEUE`
- Redis queue entries are handoff signals; the database record remains the durable state source

## Initialization and migration principles

- Laravel migration and seeder are the long-term initialization path
- raw SQL drafts may remain reference material, but not the primary lifecycle path
- baseline roles should include `inspector`, `analyst`, and `admin`
- baseline seeder should provide one idempotent core-chain example: `inspection_task + sample + sample_result + exception + analysis_job`
