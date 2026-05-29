---
title: Technology Stack Reassessment
---

# Technology Stack Reassessment

## Long-term constraint already confirmed

### Laravel must remain

Laravel stays as the long-term backend runtime because it already carries the current P0 API flow, owns the migration and seeder path, and provides a better foundation for authentication, authorization, auditing, queues, and operational management than the old lightweight PHP baseline.

This is no longer a debate about whether Laravel should stay. The real architectural question is how the rest of the delivery stack should converge around it.

## Recommended mainline

```text
Laravel API
  + React / TypeScript SPA (Vite)
  + analysis-worker (Python)
  + MariaDB
  + Redis
  + Nginx
  + Docker Compose
```

## Why React/Vite SPA is the recommended frontend direction

This project is an internal management system, not a public SEO-driven product site. The dominant frontend concerns are:

- forms, lists, and detail views
- controlled state transitions
- stable integration with Laravel APIs
- simple containerized deployment
- lower runtime and troubleshooting overhead

For that profile, a `React + TypeScript + Vite` SPA is a better operational fit than a long-running SSR application.

## Why Nuxt/Vue is not the best long-term mainline here

This is not a rejection of Vue or Nuxt as technologies. It is a project-specific engineering decision.

### 1. SSR and Nitro offer limited value for this system

The core pages are:

- inspection task lists and details
- sample workspaces
- result entry flows
- exception handling views
- analysis job status tracking
- settings and summary dashboards

These pages do not rely on SSR to unlock the main business value.

### 2. A long-running Node layer increases deployment complexity

Nuxt in production usually means:

- a persistent Node/Nitro runtime
- more moving parts in the proxy path
- broader troubleshooting scope across Nginx, Node, and API layers

For an internal management workspace, that complexity brings less return than it would for a content-heavy public-facing application.

### 3. Engineering effort should go to contracts, data, and operations

The highest-value risks in this project are:

- stable API contracts
- controlled state machine behavior
- clear MariaDB data semantics
- explicit Redis/analysis-worker async boundaries
- repeatable Docker Compose delivery

Those areas deserve the complexity budget more than SSR infrastructure does.

## Role of Docusaurus

Docusaurus is only the documentation platform. It is intentionally separate from the business frontend strategy and should stay in `website/`.

## Conclusion

| Area | Recommended direction |
| --- | --- |
| Backend | Laravel |
| Business frontend | React + TypeScript + Vite SPA |
| Async analysis | analysis-worker (Python) + Redis |
| Core database | MariaDB |
| Reverse proxy | Nginx |
| Orchestration | Docker Compose |
| Documentation site | Docusaurus |
