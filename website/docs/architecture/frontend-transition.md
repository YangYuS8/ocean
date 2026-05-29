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

### v1.4.3 repository cleanup

- the React/Vite SPA has been promoted from `frontend-spa/` to the canonical `frontend/` path
- the old Nuxt/Vue implementation has been removed from the active repository tree
- new frontend work should use `frontend/`

### v1.3.x frontend design-system correction

- the SPA is upgraded to React 19 and uses Mantine 9 as its open-source component system
- Tailwind CSS is available through the official Vite plugin for page-level layout, spacing, and utility styling
- `react-i18next` provides the in-app language switcher and translation resource boundary
- Simplified Chinese is the default operational language, with an explicit `Display language` switcher in the workspace header
- the UI direction is a restrained marine operations console inspired by Mantine dashboard/AppShell examples: off-white page background, white bordered surfaces, slate/navy text, teal primary actions, amber/red status accents, and compact operational density
- the design language uses a consistent 12px default radius, 16px major-surface radius, restrained shadows, 14-16px body text, 20-22px panel headings, and 30-36px page headings
- avoid glassmorphism, radial gradients, decorative blobs, oversized hero typography, and inconsistent one-off radii/colors unless a documented design decision supersedes this standard
- the frontend remains API-driven and static-asset deployable; component-library adoption must not move validation, workflow transitions, or audit-sensitive rules out of Laravel

### v1.3.x componentization correction

- the SPA should not keep every capability in a single giant page or a single giant `App.tsx`
- `App.tsx` should remain a composition layer only: wire the workspace hook, shell, navigation, and feature panels
- shared presentation belongs under `src/components/workspace/`
- workspace state, API orchestration, form state, and derived values belong under `src/features/workspace/`
- each business slice should have its own panel module under `src/features/workspace/panels/`
- the default page should expose section navigation for Overview, Tasks, Samples, Results, Exceptions, and Analysis instead of forcing all forms and lists into one continuous canvas
- `pnpm` is the preferred package manager for `frontend/`; keep `pnpm-lock.yaml` committed and use `pnpm run build` for SPA validation

## Repository guidance

- use `frontend/` for the active React/Vite workspace
- treat the removed Nuxt/Vue implementation as historical context available through docs and git history only
- keep docs, deployment samples, and architecture language aligned with this transition plan
- keep user-facing SPA copy in the i18n resource boundary instead of scattering hardcoded mixed-language strings across components
- keep the SPA component tree modular enough that adding v1.3.x panels does not require expanding `App.tsx`
- keep Mantine theme tokens and Tailwind utility usage aligned; do not create competing color/radius/spacing systems in ad hoc CSS
