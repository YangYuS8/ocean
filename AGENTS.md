# AGENTS.md

## What we are building

Ocean is an internal platform for marine ecological sample management and equipment inspection workflows.

The current product goal is to turn the existing MVP into a maintainable v1 system around this core chain:

```text
Inspection tasks -> Samples -> Results -> Exceptions -> Analysis jobs -> Dashboard summary
```

## Architecture direction

- Keep **Laravel** as the long-term backend runtime. This is a project constraint.
- Keep the active business frontend in `frontend/` as a **React + TypeScript + Vite SPA**, served as static assets through Nginx.
- Treat the removed Nuxt/Vue frontend only as historical context available through documentation and git history.
- Keep **MariaDB** as the core transactional database.
- Keep **Redis** as the async boundary between Laravel and `analysis-worker`.
- Keep `analysis-worker/` responsibilities isolated to analysis, image processing, model inference, and result write-back.
- Keep deployment simple and repeatable with **Nginx + Docker Compose**, with Docker assets under `docker/`.
- Keep project documentation in the Docusaurus site under `website/`.

## Documentation rules

- English is the primary documentation language.
- Simplified Chinese must be maintained through Docusaurus i18n under `website/i18n/zh-Hans/`.
- Do not restore the deleted root-level `docs/` or `openspec/` directories as primary documentation locations.
- Update `README.md` only as a concise project entry point; detailed architecture, API, data, operations, and roadmap content belongs in `website/`.
- When changing architecture or roadmap assumptions, update both English docs and Simplified Chinese i18n docs.

## Development rules

- Preserve the Laravel API contract unless a change explicitly updates the documented contract.
- Keep business rules, validation, state transitions, and audit-sensitive behavior in Laravel rather than the frontend.
- Keep frontend code API-driven; do not introduce SSR-only assumptions for the long-term product direction.
- Keep analysis-worker code behind clear job boundaries; do not let it mutate core business state outside defined write-back paths.
- Keep database changes migration-driven and document any state-machine or entity-semantic change.
- Prefer small, reviewable changes with explicit validation steps.
- Run the most relevant checks before handing off changes. For documentation-site changes, run:

```bash
cd website
npm run build
```

## GitHub Actions notes

- The documentation site is deployed by `.github/workflows/docs-pages.yml`.
- Keep `website/package-lock.json` committed so GitHub Actions can use `npm ci` reproducibly.
- If Docusaurus `future.v4` options remain enabled, keep `@docusaurus/faster` installed because the build can require it in CI.
