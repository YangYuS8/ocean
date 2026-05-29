---
title: Contributing Guide
---

# Contributing Guide

This guide summarizes how to make changes to Ocean without drifting away from the v1 architecture direction.

## Project direction

Ocean is an internal platform for marine ecological sample management and equipment inspection workflows.

The current v1 product chain is:

```text
Inspection tasks -> Samples -> Results -> Exceptions -> Analysis jobs -> Dashboard summary
```

Keep the long-term runtime shape stable:

- Laravel owns backend runtime, validation, workflow transitions, API contracts, and audit-sensitive behavior.
- React 19 + TypeScript + Vite in `frontend/` is the default business frontend.
- MariaDB is the transactional database.
- Redis is the async handoff boundary between Laravel and analysis workers.
- Analysis workers stay focused on analysis, image processing, model inference, and result write-back.
- Nginx + Docker Compose remain the simple deployment target.
- Detailed project documentation lives in `website/`.

## Repository layout

- `backend/`: Laravel application and legacy lightweight PHP reference baseline.
- `frontend/`: default React 19 + TypeScript + Vite SPA, built with Mantine, Tailwind CSS, and react-i18next.
- `analysis-worker/`: analysis worker implementation and model runtime.
- `docker/`: Docker Compose, image, and reverse-proxy configuration.
- `website/`: bilingual Docusaurus documentation site.

v1.4.3 normalized this layout: the active SPA uses the canonical frontend path, the analysis worker source uses the `analysis-worker/` path, and Docker-related assets live under `docker/` with local and production Compose files named explicitly.

Do not restore deleted root-level `docs/` or `openspec/` directories as primary documentation locations.

## Branch and pull request expectations

Keep changes small and reviewable. Prefer one coherent change per branch:

- feature work: `feat/...`
- fixes: `fix/...`
- documentation: `docs/...`
- dependency or tooling work: `chore/...`

Before opening a PR:

1. Rebase or merge current `main`.
2. Run the checks that match your changes.
3. Update English and Simplified Chinese docs when behavior, architecture, roadmap, API, operations, or design assumptions change.
4. Describe the validation commands you ran.

## Backend development rules

Laravel is the business source of truth. Keep these rules intact:

- Preserve documented API contracts unless the PR explicitly updates the documented contract.
- Keep validation, state transitions, workflow rules, and audit-sensitive behavior in Laravel.
- Use migrations for database changes.
- Document state-machine or entity-semantic changes in `website/docs/data/data-model-and-states.md` and the Chinese counterpart.
- Keep Python from mutating core business state outside defined write-back APIs.
- Prefer Laravel conventions and existing project structure over ad hoc folders.

Laravel Boost is installed as a project-local development dependency under `backend/`. Use its local Artisan commands from the backend directory when working on Laravel-specific tasks.

Useful commands:

```bash
cd backend
composer validate --strict
php artisan route:list --path=api
php artisan test --compact
```

If host PHP lacks SQLite PDO, run backend tests in Docker instead:

```bash
docker compose up -d
docker compose exec -T php php /var/www/html/artisan test --compact
```

## Frontend SPA development rules

`frontend/` is the default business frontend. The older Nuxt app has been removed from the active repository layout.

Use `pnpm` in `frontend/`:

```bash
cd frontend
pnpm install
pnpm run build
```

Frontend architecture rules:

- Keep API access centralized in `src/api/client.ts` or a similarly typed API boundary.
- Keep `App.tsx` as a composition layer, not a dumping ground for business logic and forms.
- Put shared workspace UI under `src/components/workspace/`.
- Put workspace state, API orchestration, derived values, and feature panels under `src/features/workspace/`.
- Keep user-facing text in the i18n resource boundary instead of hardcoding mixed-language strings in components.
- Do not introduce SSR-only assumptions into the long-term SPA.

Design-system rules:

- Use Mantine for accessible components and theme tokens.
- Use Tailwind CSS for page-level layout, spacing, and utility styling.
- Keep the visual language aligned with the documented frontend transition plan: off-white page background, white bordered surfaces, slate/navy text, teal primary actions, amber/red status accents, compact operational density.
- Use consistent radii: about 12px for controls/cards and 16px for major surfaces.
- Avoid glassmorphism, radial gradients, decorative blobs, oversized hero typography, and one-off colors/radii that are not part of the theme.

Mantine skills for frontend work are stored under `frontend/.opencode/skills/` and registered by the root `opencode.json`.

## Legacy frontend rules

The old Nuxt/Vue implementation is no longer part of the active runtime tree. Do not reintroduce it unless a PR explicitly documents the need and migration impact.

Validation command:

```bash
cd frontend
pnpm install
pnpm run build
```

## Analysis worker rules

Analysis workers own analysis and inference work, not core business workflow authority. They are currently implemented in Python under `analysis-worker/`.

- Use Redis as the async handoff boundary.
- Use Laravel APIs for job status transitions and result write-back.
- Keep worker changes behind explicit job boundaries.
- Update operations docs when worker startup, queue names, or environment variables change.

## Documentation rules

English is the primary documentation language. Simplified Chinese must be maintained through Docusaurus i18n under:

```text
website/i18n/zh-Hans/docusaurus-plugin-content-docs/current/
```

Use these locations:

- Architecture: `website/docs/architecture/`
- Product scope and roadmap: `website/docs/product/`
- API contracts: `website/docs/api/`
- Data model and states: `website/docs/data/`
- Operations: `website/docs/operations/`

For documentation-site changes, run:

```bash
cd website
npm run build
```

Keep `website/package-lock.json` committed so GitHub Actions can use `npm ci` reproducibly.

## Validation checklist

Run the smallest relevant set of checks for your change:

| Change area | Minimum validation |
| --- | --- |
| `backend/` Laravel code | `composer validate --strict`, `php artisan test --compact` or Docker equivalent |
| `frontend/` | `pnpm run build` |
| `website/` | `npm run build` |
| Docker/Nginx routing | `docker compose up -d --build`, smoke `/`, a deep SPA route, and `/api/dashboard/summary` |
| Redis/Python async loop | queue/job lifecycle smoke plus relevant backend tests |

For a release candidate, prefer this full set:

```bash
cd frontend && pnpm install --frozen-lockfile && pnpm run build
cd ../frontend && pnpm install --frozen-lockfile && pnpm run build
cd ../website && npm run build
cd ../backend && composer validate --strict
cd .. && docker compose up -d
docker compose exec -T php php /var/www/html/artisan test --compact
curl -fsS http://127.0.0.1:8080/ >/tmp/ocean-root.html
curl -fsS http://127.0.0.1:8080/samples/123 >/tmp/ocean-deep.html
curl -fsS http://127.0.0.1:8080/api/dashboard/summary
```

## Dependency updates

Dependency PRs still need review. Check what changed, run the affected build, and avoid merging upgrades that conflict with peer dependency ranges or the documented architecture direction.

Current package-manager expectations:

- `frontend/`: `pnpm`, commit `pnpm-lock.yaml`.
- `website/`: `npm`, commit `package-lock.json`.
- `backend/`: Composer, commit `composer.lock`.

## Release expectations

Before publishing a version tag:

1. Ensure `main` is clean and up to date with `origin/main`.
2. Review and merge or explicitly defer open PRs.
3. Run release-candidate validation.
4. Confirm docs are synchronized in English and Simplified Chinese.
5. Create and push an annotated tag, for example `v1.3.0`.
6. Confirm the GitHub release workflow succeeds.

Release notes are generated by the release workflow. Keep commit messages concise and grouped by function where possible.
