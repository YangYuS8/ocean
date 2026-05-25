---
title: v1 Roadmap
---

# v1 Roadmap

This roadmap turns the v1 line into a sequence of semantic versions so the team can reason about delivery scope, acceptance, and dependencies with more precision.

## v1.0.0 — Architecture Baseline / Contract Freeze

### Goal

Lock down the backend, data, and API baseline required for all later v1 work.

### Scope

- confirm Laravel as the long-term backend runtime
- freeze the P0 API surface and response conventions
- freeze core MariaDB entity semantics and state transitions
- consolidate project documentation into the Docusaurus site

### Primary deliverables

- Laravel-backed P0 API contract baseline
- documented data model and state machine baseline
- Docusaurus documentation site with English default and Simplified Chinese i18n
- GitHub Pages workflow for docs publishing

### Exit criteria

- the team agrees on the long-term stack direction
- P0 API and state semantics are documented in one stable location
- `website/` builds successfully and becomes the documentation source of truth
- frozen response conventions include unified list `meta`, including `GET /api/samples/{id}/results`
- sample P0 state advancement is explicitly backend-owned for result intake
- initialization includes an idempotent seeded core-chain verification example

## v1.1.0 — Frontend Transition Foundation

### Goal

Prepare the business frontend to move toward a Laravel API + React/TypeScript SPA mainline.

### Scope

- define the transition target from the current frontend phase to the SPA mainline
- identify API consumption boundaries required by the new frontend
- keep deployment assumptions aligned with Nginx and Compose
- ship a minimal React/Vite workspace skeleton without replacing the current Nuxt runtime

### Primary deliverables

- documented frontend transition baseline
- `frontend-spa/` React + TypeScript + Vite scaffold
- API integration assumptions for the new SPA
- deployment notes covering static asset serving through Nginx
- target static build and container examples that do not break the current Compose line

### Exit criteria

- the frontend direction is unambiguous for the delivery team
- no critical product area depends on SSR-specific behavior
- the target integration contract for the SPA is documented
- the repository contains a buildable SPA baseline with configurable `VITE_API_BASE`
- the Nuxt implementation remains available for the current running path during transition

## v1.2.0 — Core Workspace Completion

### Goal

Complete the core internal workspace around inspection tasks, samples, results, exceptions, and summary views.

This is the version where the React/Vite frontend is expected to move beyond the transition skeleton and take on the main core workspace delivery.

### Scope

- inspection task list, detail, and minimum actions
- sample list, detail, and creation flow
- sample result entry and retrieval
- exception creation and resolution
- dashboard summary integration

### Primary deliverables

- a usable internal workspace for day-to-day MVP operations
- connected P0 UI flows against Laravel APIs
- stable user paths through task, sample, result, and exception workflows

### Exit criteria

- users can complete the core P0 chain without placeholder-only pages
- key list/detail/action flows are operational against live APIs
- failure and empty states are addressed for core pages

## v1.3.0 — Async Analysis Loop

### Goal

Make analysis jobs a real, traceable async execution loop instead of a static record system.

### Scope

- create and track analysis jobs
- preserve Redis as the async boundary
- run Python worker processing against queued jobs
- write back success, failure, retry, and summary states

### Primary deliverables

- working `analysis_jobs` lifecycle flow
- Python worker execution path for supported job types
- retry semantics that preserve failed history and create new queued jobs
- operational visibility into running and failed analysis work

### Exit criteria

- analysis jobs move through their defined lifecycle with traceable status changes
- failed tasks can be retried without mutating historical failed records
- users can see useful status and next-step guidance after completion

### v1.3.0 implementation note

The v1.3 line uses MariaDB as the durable `analysis_jobs` source of truth and Redis as the worker handoff boundary. Laravel stores the job row, pushes a compact payload to `ANALYSIS_JOB_REDIS_QUEUE`, and the Python worker consumes that queue before calling the Laravel API to start, succeed, or fail the job. The worker still keeps the HTTP queued-list path as a compatibility fallback for older environments.

## v1.4.0 — Governance and Operations

### Goal

Add the governance and runtime controls required for a more dependable internal system.

### Scope

- authentication and identity injection strategy
- baseline RBAC
- audit trail improvements
- operational runbooks and validation routines

### Primary deliverables

- a documented and implementable auth direction
- baseline role and permission model
- clearer audit expectations for sensitive actions
- improved operational guidance for deployment and maintenance

### Exit criteria

- the system has a clear path away from manually supplied identity fields
- high-value actions have defined audit expectations
- operators have stable documentation for common runtime tasks

## v1.5.0 — Release Hardening

### Goal

Harden the v1 line for repeatable release, onboarding, and controlled expansion.

### Scope

- strengthen validation and regression confidence
- align build, deployment, and documentation expectations
- reduce ambiguity across product, architecture, and operational documentation

### Primary deliverables

- cleaner release checklist and validation expectations
- documentation consistency across architecture, API, data, and operations
- reduced risk when onboarding new contributors or shipping incremental changes

### Exit criteria

- release readiness no longer depends on scattered tribal knowledge
- architecture and delivery guidance are internally consistent
- the v1 line is stable enough for controlled iteration beyond the initial MVP phase
