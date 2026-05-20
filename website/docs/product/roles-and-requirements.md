---
title: Roles and Requirements
---

# Roles and Requirements

This page consolidates the old role analysis, functional requirements, and non-functional requirements into a single product-facing summary.

## Core roles

| Role | Core responsibility | MVP priority |
| --- | --- | --- |
| Inspector | Execute inspections, record field information, submit samples and exceptions | Task viewing, state updates, sample entry, exception reporting |
| Analyst | Receive samples, enter results, review analysis status | Sample intake, sample lookup, result entry, analysis job monitoring |
| Administrator | Maintain operational boundaries and core configuration | Roles and access, dictionary/config management, audits, global summary |

## MVP functional scope

### 1. Inspection tasks

- View task lists and details
- Move tasks through `assigned -> in_progress -> submitted`
- Record field notes, samples, and exceptions

### 2. Sample management

- Create samples
- View sample lists and details
- associate samples with inspection tasks
- upload a main image and surface automated suggestions

### 3. Sample results

- Enter structured results
- View result history
- reserve room for review-oriented state expansion

### 4. Exception handling

- Create exceptions
- View exception lists
- Move exceptions from `open -> resolved`

### 5. Analysis jobs

- Create analysis jobs
- Track lifecycle status
- Handle `queued / running / succeeded / failed / cancelled`
- Retry failed jobs while keeping historical records

### 6. Dashboard summary

- pending samples
- today’s inspection tasks
- open exceptions
- queued analysis jobs

## Non-functional priorities

### Usability

- Keep the core workspace easy to operate
- Minimize free-form text where structured forms and explicit actions are sufficient

### Performance and capacity

- Keep list, detail, and summary APIs stable under ordinary usage
- Move long-running analysis workloads out of the synchronous request path

### Reliability and recovery

- Prevent ordinary failures from losing core data
- Preserve failure history and retry semantics for async jobs

### Security

- enforce role-based access
- keep audit trails for sensitive actions
- validate input consistently

### Maintainability

- preserve clear boundaries across Laravel, the SPA frontend, Python workers, MariaDB, Redis, Nginx, and Compose
- avoid premature microservice or orchestration expansion

## Explicitly out of scope for the current phase

- offline sync and conflict resolution
- GPS / BeiDou geo-fencing
- video-heavy media pipelines
- complex real-time equipment telemetry
- model version management and gradual release
- deep third-party platform integrations

## Priority conclusion

The current phase should prioritize:

1. runnable end-to-end workflows
2. traceable data movement
3. auditable state transitions
4. simple and stable deployment
