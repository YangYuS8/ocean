---
title: Frontend Transition Plan
---

# Frontend Transition Plan

## Why this document exists

The repository currently contains a working Nuxt/Vue frontend from the earlier MVP phase, while the long-term architecture direction has already been set to `Laravel API + React + TypeScript + Vite SPA`.

v1.1.0 is the bridge between those two realities.

## Current state in v1.1.0

### Transitional runtime kept in place

- `frontend/` remains the current runnable frontend
- current Compose and Nginx defaults still support that path
- no forced cutover happens in v1.1.0

### Target mainline introduced

- `frontend-spa/` is added as a new workspace
- it builds as a static SPA
- it reads `VITE_API_BASE` and defaults to `/api`
- it centralizes API access through `src/api/client.ts`

## What v1.1.0 delivers

v1.1.0 intentionally delivers foundation rather than full feature parity.

The expected outputs are:

1. a buildable React/Vite SPA skeleton
2. a clear API boundary against Laravel
3. a static hosting example for Nginx
4. a container build example that does not disturb the current Compose path

## Explicit non-goals for v1.1.0

v1.1.0 does **not**:

- delete `frontend/`
- replace the current running Nuxt deployment path by default
- complete the business workspace UI
- move business rules out of Laravel

## API boundary rules for the new SPA

The SPA should treat Laravel as the source of truth for:

- workflow state transitions
- validation
- audit-sensitive actions
- list and detail data contracts
- async job creation and status reporting

The SPA should own:

- page composition
- client-side state for user interaction
- presentation concerns
- request orchestration through a typed or centralized API client layer

## Deployment shape

The target production shape remains:

```text
Browser
  -> Nginx
      -> frontend-spa/dist
      -> /api/ -> Laravel / PHP
```

This keeps the business frontend deployable as static assets and avoids introducing a required long-running Node runtime for the workspace.

## Versioned transition expectation

### v1.1.0

- establish SPA skeleton
- prove API boundary
- provide deployment samples
- preserve current Nuxt runtime

### v1.2.0

- implement the core inspection / sample / result / exception workspace in the SPA
- make the SPA the main delivery vehicle for the core internal workspace
- reduce remaining dependency on the transitional Nuxt path

### v1.3.x correction

- the default Docker Compose and Nginx entrypoint now serve `frontend-spa/` instead of the transitional Nuxt runtime
- `frontend/` remains in the repository only as an earlier-phase reference implementation
- the long-term default path is now Browser -> Nginx -> React/Vite SPA static assets -> `/api/` Laravel

## Repository guidance

- keep `frontend/` intact as reference material unless a later version explicitly retires it
- place new long-term workspace frontend work under `frontend-spa/`
- keep docs, deployment samples, and architecture language aligned with this transition plan
