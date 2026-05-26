---
title: P0 API
---

# P0 API

This page consolidates the old MVP API scope, field drafts, and OpenSpec requirements into a single contract summary.

## Unified response convention

### Success

```json
{
  "data": {}
}
```

### List response

```json
{
  "data": [],
  "meta": {
    "page": 1,
    "page_size": 20,
    "total": 0
  }
}
```

### Error

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "sample_type is required"
  }
}
```

## P0 domains and endpoints

### Governance and audit

- `POST /api/auth/login`
- `GET /api/auth/me`
- `POST /api/auth/logout`
- `GET /api/governance/me`
- `GET /api/governance/roles`
- `GET /api/audit-events`

The SPA now uses token login as the primary authentication path:

```http
POST /api/auth/login
Content-Type: application/json

{
  "username": "admin",
  "password": "password"
}
```

Successful login returns a bearer token and actor metadata:

```json
{
  "data": {
    "token": "...",
    "actor": {
      "id": 1,
      "username": "admin",
      "display_name": "系统管理员",
      "roles": ["admin"]
    }
  }
}
```

Protected mutation requests must send:

```http
Authorization: Bearer <token>
```

`GET /api/auth/me` returns the current token actor. `POST /api/auth/logout` revokes the current token.

The v1.4.0 internal identity bridge uses the request header:

```http
X-Ocean-Actor-Id: 3
```

When present, Laravel resolves the active `users` record, injects the actor into the request context, and applies baseline RBAC to sensitive mutation endpoints. During the v1.4 transition, legacy identity payload fields are still accepted as fallback so older worker and frontend flows continue to operate.

`X-Ocean-Actor-Id` is no longer the primary frontend authentication path. It remains an internal transition bridge for non-SPA tooling only.

`GET /api/governance/roles` returns the baseline role catalog and permissions. `GET /api/audit-events` returns paginated audit events and supports filters such as `event_type`, `resource_type`, `resource_id`, `actor_id`, and `actor_source`.

### Dashboard

- `GET /api/dashboard/summary`

Minimum fields:

- `pending_samples`
- `today_inspection_tasks`
- `open_exceptions`
- `queued_analysis_jobs`

### Inspection Tasks

- `GET /api/inspection-tasks`
- `GET /api/inspection-tasks/{id}`
- `POST /api/inspection-tasks/{id}/start`
- `POST /api/inspection-tasks/{id}/submit`

Typical filters:

- `status`
- `assigned_to`
- `task_type`
- `planned_date_from`
- `planned_date_to`
- `keyword`

### Samples

- `GET /api/samples`
- `POST /api/samples`
- `GET /api/samples/{id}`
- `POST /api/samples/{id}/main-image`
- `GET /api/samples/{id}/main-image/content`
- `GET /api/samples/{id}/image-suggestion`

### Sample Results

- `GET /api/samples/{id}/results`
- `POST /api/samples/{id}/results`

Frozen v1.0.0 rules:

- `raw_value` and `normalized_value` use JSON structures
- new result records start in `draft`
- `GET /api/samples/{id}/results` is a unified list response and always returns `data + meta`
- creating a result never relies on frontend-driven sample status changes; the backend applies sample state rules

### Exceptions

- `GET /api/exceptions`
- `POST /api/exceptions`
- `POST /api/exceptions/{id}/resolve`

### Analysis Jobs

- `GET /api/analysis-jobs`
- `POST /api/analysis-jobs`
- `GET /api/analysis-jobs/{id}`
- `POST /api/analysis-jobs/{id}/start`
- `POST /api/analysis-jobs/{id}/succeed`
- `POST /api/analysis-jobs/{id}/fail`
- `POST /api/analysis-jobs/{id}/cancel`
- `POST /api/analysis-jobs/{id}/retry`

## Key field and behavior constraints

### Sample creation

Suggested required fields:

- `sample_code`
- `sample_type`

New samples start in `registered`.

### Transitional identity fields

The v1.4.0 phase prefers token-authenticated actor injection and still allows explicit request fields as compatibility fallback:

- `operator_id`
- `entered_by`
- `queued_by`
- `reported_by`
- `resolved_by`

If both token/header actor identity and a legacy payload field are present, Laravel uses the authenticated or injected actor. These fields remain transitional and should later be removed from user-initiated API contracts.

### Baseline RBAC permissions

Sensitive actions require either a header actor or the dedicated internal worker bridge:

| Role | Permissions |
| --- | --- |
| `admin` | all permissions |
| `inspector` | start/submit inspection tasks, create samples, upload sample images, create exceptions |
| `analyst` | create sample results, create/resolve exceptions, create/cancel/retry analysis jobs |
| `worker` | start/succeed/fail analysis jobs |

Sensitive user-initiated mutation requests require a bearer token. Legacy payload identity fields are preserved for actor attribution compatibility, but they do not authorize protected write routes by themselves.

Python worker status callbacks use the internal worker bridge:

```http
X-Ocean-Worker: ocean-python-worker
```

This bridge maps to the seeded `worker01` actor and is only intended for internal Compose/network use until a real worker credential is introduced.

Seeded demo users for local development are:

| Username | Password | Role |
| --- | --- | --- |
| `admin` | `password` | `admin` |
| `inspector01` | `password` | `inspector` |
| `analyst01` | `password` | `analyst` |
| `worker01` | `password` | `worker` |

### Audit event expectations

Laravel records audit events for high-value actions including:

- inspection task start and submit
- sample creation and main-image upload
- sample result creation
- exception open and resolve
- analysis job queue, start, success, failure, cancel, and retry

Audit events include `event_type`, `resource_type`, `resource_id`, `actor_id`, `actor_source`, optional metadata, and `created_at`.

## P1 candidates that should not block the current delivery line

- `POST /api/inspection-tasks`
- `POST /api/inspection-tasks/{id}/complete`
- `PATCH /api/samples/{id}`
- `POST /api/samples/{id}/receive`
- `POST /api/samples/{id}/attachments`
- `POST /api/sample-results/{id}/submit-review`
- `POST /api/sample-results/{id}/approve`
- `POST /api/sample-results/{id}/reject`
- `GET /api/reports/overview`

## Explicitly out of current P0 scope

- offline synchronization
- geo-fencing
- video pipelines
- complex real-time device telemetry
- model version management
- advanced reporting orchestration
- third-party platform integrations
