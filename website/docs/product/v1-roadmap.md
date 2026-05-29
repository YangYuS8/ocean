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

### v1.3.x correction

The default Compose/Nginx runtime now serves the React/Vite SPA, completing the practical frontend cutover that v1.2.0 intended. The old Nuxt implementation remains available as repository reference material, not the default runtime.

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

## v1.3.x — Frontend Usability and Componentization Follow-through

### Goal

Turn the default SPA from a connected MVP workspace into a maintainable React 19 operational console.

### Scope

- make language switching explicit and visible in the workspace header
- replace the single-canvas workspace with section navigation for Overview, Tasks, Samples, Results, Exceptions, and Analysis
- split the frontend into reusable workspace components, feature panels, and a dedicated workspace state/API hook
- standardize `frontend-spa/` local development and container build commands on `pnpm`
- keep i18n resources as the only place for user-facing SPA labels
- unify the visual language around Mantine theme tokens plus Tailwind utility classes, rather than scattered one-off CSS

### Primary deliverables

- `App.tsx` reduced to composition rather than business logic and form orchestration
- shared components under `src/components/workspace/`
- workspace feature code under `src/features/workspace/`
- committed `pnpm-lock.yaml` for reproducible SPA installs
- updated Docker build path using `pnpm install --frozen-lockfile` and `pnpm run build`
- documented frontend visual standards for typography scale, color palette, border radius, shadows, layout density, and anti-patterns to avoid

### Exit criteria

- users can find the Chinese/English language switch without hunting through dense controls
- each major workspace capability is reachable through a clear section tab
- no new v1.3.x workspace feature requires appending another large block to `App.tsx`
- `pnpm run build` succeeds in `frontend-spa/`
- `website` documentation remains synchronized in English and Simplified Chinese
- new SPA UI work follows the documented Mantine/Tailwind design language instead of introducing ad hoc gradients, oversized typography, or inconsistent radii

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

### v1.4.0 implementation note

The v1.4 line introduces SPA token login through `POST /api/auth/login`, baseline RBAC permissions for high-value mutation routes, and an `audit_events` table for task, sample, result, exception, and analysis-job actions. Legacy payload identity fields remain accepted for attribution compatibility, but protected user mutations require `Authorization: Bearer <token>`. `X-Ocean-Actor-Id` remains only as an internal transition bridge for non-SPA tooling, and the Python worker uses a separate internal worker bridge until a stronger worker credential is introduced.

## v1.4.1 — Settings and User Management Consolidation

### Goal

Consolidate scattered operational preferences and user identity controls into first-class SPA pages before expanding additional governance features.

### Scope

- add a Settings page as the single user-facing entry point for workspace-level options that are currently scattered across the shell and feature panels
- add a Users page for administrators to view, create, edit, activate/deactivate, and inspect role assignments for internal users
- add a personal profile path for the current user to review account information and update safe self-service fields
- keep permission-sensitive user mutations in Laravel with RBAC and audit coverage rather than implementing client-only controls
- decide which preferences are local-only UI preferences and which require backend persistence before development starts

### Primary deliverables

- SPA navigation entries for Settings and Users
- documented Settings information architecture, including language, display, workspace behavior, and future notification/import-export placeholders
- documented user-management API contract for admin-managed user updates and current-user profile updates
- backend user-management endpoints guarded by baseline RBAC
- audit events for sensitive user administration actions such as role changes, activation changes, and password resets once those actions are implemented

### Exit criteria

- language and other workspace preferences no longer feel hidden inside unrelated controls
- admins have one clear page for user administration instead of relying on seeded data or ad hoc backend changes
- non-admin users can see their own identity and profile details without gaining administrative user-management permissions
- all user mutations are validated and authorized by Laravel
- English and Simplified Chinese docs describe the Settings/User Management scope before implementation is marked complete

### v1.4.1 implementation note

v1.4.1 ships Settings and Users as first-class SPA tabs. Settings combines current-user profile editing, language preference, display density, default workspace tab, and runtime/auth summary. Users gives administrators one page for user listing, filtering, creation, profile/status/password edits, role replacement, activation, and deactivation. Laravel owns all mutations through token-authenticated APIs, persists user preferences in `user_preferences`, and records audit events for user administration, profile updates, and settings updates. Non-admin users can update their own profile/settings but cannot access administrative user management.

## v1.4.2 — Production App Image and Worker Naming

### Goal

Make production deployment easier by packaging the main web application into one deployable image and making service names reflect product responsibilities instead of implementation languages.

### Scope

- add a production `ocean-app` image that combines Nginx, PHP-FPM, Laravel API code, Composer production dependencies, and the built React/Vite SPA
- keep MariaDB and Redis as separate stateful infrastructure services
- keep the async analysis worker as a separate runtime service, but rename the current `python` service to a responsibility-oriented name such as `analysis-worker`
- make the analysis worker image self-contained enough for production by copying worker source and required model/runtime assets instead of relying on development bind mounts
- preserve the Redis boundary between Laravel and the analysis worker
- define a production Compose profile or production Compose file that uses the combined app image while keeping local development ergonomics clear

### Primary deliverables

- `docker/app/Dockerfile` or equivalent multi-stage production Dockerfile for Laravel + SPA
- production Nginx config that serves SPA assets directly and routes `/api/` to Laravel `public/index.php` through local PHP-FPM
- app entrypoint or supervisor configuration for running Nginx and PHP-FPM in the same app container
- Compose changes introducing an `app` service for production and renaming the worker-facing service from `python` to `analysis-worker`
- a production-ready analysis worker image path, or an explicit documented follow-up if model packaging remains external
- updated operations documentation covering migration execution, storage volumes, worker API base URL, and the renamed services

### Exit criteria

- production deployment can run the main web app from one immutable image without bind-mounting `backend/` or `frontend-spa/`
- the deployment topology is reduced from `frontend + nginx + php + db + redis + python` to `app + db + redis + analysis-worker` for production
- uploaded/public storage remains persistent and shareable with the analysis worker where needed
- the worker service name communicates its role in the product chain rather than the implementation language
- `docker compose config` and the relevant app-image build command succeed
- existing backend, SPA, and documentation validation commands still pass

### v1.4.2 implementation note

v1.4.2 ships the production packaging baseline. `compose.prod.yml` introduces an `app` service backed by `docker/app/Dockerfile`, which builds the React/Vite SPA, installs Laravel production Composer dependencies, serves SPA assets through Nginx, and routes `/api/` to local PHP-FPM in the same container. The default and production Compose paths now use `analysis-worker` as the service name for asynchronous model/inference execution, while the implementation source remains in `python/`. The analysis worker image copies worker code and model assets at build time for production use. MariaDB and Redis remain separate services, and migrations remain an explicit deployment step unless `OCEAN_RUN_MIGRATIONS=true` is intentionally enabled.

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
