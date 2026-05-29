---
title: Data Model and State Flows
---

# Data Model and State Flows

This page consolidates the old data-model draft, state-transition draft, SQL planning notes, and related OpenSpec constraints.

## Core tables

The P0/v1.4.1 baseline uses eleven core tables:

- `users`
- `roles`
- `user_roles`
- `inspection_tasks`
- `samples`
- `sample_results`
- `exceptions`
- `analysis_jobs`
- `audit_events`
- `api_tokens`
- `user_preferences`

## Data relationships

```text
users ----< inspection_tasks ----< samples ----< sample_results
  |                |                    |            \
  |                |                    |             \-> exceptions
  |                |                    |
  |                |                    \-> analysis_jobs
  |
  \----< user_roles >---- roles

users ----< audit_events >---- auditable resources
users ----< api_tokens
users ---- user_preferences
```

`exceptions` uses `resource_type + resource_id` to reference tasks, samples, or result records.
`audit_events` also uses `resource_type + resource_id` so it can record events across tasks, samples, results, exceptions, and analysis jobs without coupling the audit log to one table.

## Database implementation baseline

- target database: `MariaDB`
- charset: `utf8mb4`
- primary key strategy: `BIGINT UNSIGNED AUTO_INCREMENT`
- time fields: `DATETIME`
- state fields: `VARCHAR(20)`
- JSON fields: used for result content and analysis job parameters / summaries

## Governance baseline

v1.4.0 adds a baseline governance layer without changing the long-term Laravel ownership model:

- `X-Ocean-Actor-Id` is the internal identity injection bridge.
- SPA login uses database-backed bearer tokens stored in `api_tokens`.
- `users`, `roles`, and `user_roles` remain the baseline identity/RBAC tables.
- v1.4.1 user-facing workspace preferences are persisted in `user_preferences` and owned by a single `users` row.
- baseline seeded roles are `admin`, `inspector`, `analyst`, and `worker`.
- legacy payload identity fields remain accepted only as an attribution compatibility path.
- `audit_events.actor_source` records whether attribution came from `request_header` or legacy `payload`.

### user_preferences

`user_preferences` keeps user-specific workspace settings separate from global identity data:

- `user_id` is unique and references `users.id`
- `language` stores the preferred UI locale, such as `zh-Hans` or `en`
- `display_density` stores lightweight display preference, currently `comfortable` or `compact`
- `default_workspace_tab` stores the preferred landing tab in the SPA workspace
- `settings_json` is reserved for future per-user preference extensions without changing core identity semantics

The profile fields that identify a person remain in `users` (`username`, `display_name`, `email`, `status`). Role assignments remain in `user_roles`. Settings are not authorization data and must not be used for RBAC decisions.

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
- baseline roles should include `inspector`, `analyst`, `admin`, and `worker`
- baseline seeder should provide one idempotent core-chain example: `inspection_task + sample + sample_result + exception + analysis_job`
