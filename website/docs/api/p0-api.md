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

Current rules:

- `raw_value` and `normalized_value` use JSON structures
- new result records start in `draft`

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

The current phase still allows explicit request fields such as:

- `operator_id`
- `entered_by`
- `queued_by`
- `reported_by`
- `resolved_by`

These are transitional and should later move to authenticated Laravel-side injection.

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
