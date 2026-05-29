---
title: OpenSpec Decision Summary
---

# OpenSpec Decision Summary

This page replaces the deleted root `openspec/` directory as the human-facing summary of historical decisions that still matter.

## 1. Backend workspace and runtime

Historical decisions already established that:

- the backend workspace belongs in `backend/`
- PHP runtime configuration belongs under `docker/php/`
- Laravel is the long-term backend runtime

## 2. P0 API and database foundation

The older OpenSpec material already converged on these stable boundaries:

- eight core MariaDB tables for P0
- Laravel migrations and seeders as the long-term initialization path
- P0 APIs across dashboard, tasks, samples, results, exceptions, and analysis jobs
- a unified response structure

## 3. Sample workflow and analysis jobs

The historical specs consistently treated:

- samples as the main long-term business object
- sample detail as the center for results, exceptions, and analysis jobs
- analysis jobs as a minimum lifecycle process with retry semantics
- retry-after-failure as “create a new queued record and preserve the old failure”

## 4. Reassessment of the older frontend direction

Earlier OpenSpec decisions focused on a Nuxt-based workspace because that was a practical short-term path for building out the initial MVP shell.

From a longer-term perspective, the better engineering convergence is now:

- `Laravel API`
- `React/TypeScript SPA (Vite)`
- `Python Worker`
- `MariaDB`
- `Redis`
- `Nginx`
- `Docker Compose`

That means:

- **keeping Laravel is still a stable conclusion**
- **Nuxt-related decisions should be treated as stage-specific history, not the long-term primary direction**

## 5. Historical constraints that remain valid

The following still matter:

- API paths and field semantics should remain stable where possible
- state transitions should be driven through explicit actions
- data semantics should not be rewritten casually during stack transitions
- Python and Redis should remain async boundaries rather than being pushed back into the synchronous transactional path

## 6. What this documentation migration accomplished

After the current restructuring:

- the old business design documents were merged into `website/docs/`
- the historical OpenSpec decision trail was distilled into this documentation site
- the root `docs/` and `openspec/` directories remain deleted
