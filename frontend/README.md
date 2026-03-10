# Ocean Frontend

Nuxt 4 frontend for the ocean sample and inspection system.

## Template Basis

This frontend is adapted from the Nuxt UI Dashboard template:

- Template: `github:nuxt-ui-templates/dashboard`
- Runtime: Nuxt 4 Node/Nitro on port `3000`
- Package manager: `pnpm`

The project keeps the existing deployment model:

- Nuxt serves the frontend on `3000`
- Nginx proxies browser traffic to Nuxt
- `/api/` remains routed to the PHP backend

## Workspace Modules

- Overview
- Sample Management
- Inspection Tasks
- Equipment Monitoring
- Reports
- Settings

The current UI focuses on the dashboard shell, module entry pages, responsive navigation and project branding. Real business metrics and backend integrations are planned for follow-up work.

## Template Adaptation Decisions

- Kept: dashboard sidebar, responsive panel layout and command/search entry for fast workspace switching.
- Deferred: notification drawer and heavier demo interactions until real alert data is available.
- Replaced: template labels, page structure and homepage content with ocean sample and inspection terminology.

## Integration Roadmap

- PHP `/api/` will provide workspace-facing business data such as sample lists, inspection tasks and report summaries.
- Redis-backed async tasks will later surface queue status, analysis progress and alert triggers in the dashboard.
- Python analysis results will be exposed through backend APIs after image-processing and statistical workflows are stabilized.

## Setup

```bash
pnpm install
```

## Development

```bash
pnpm dev
```

The development server listens on `http://localhost:3000`.

## Validation

```bash
pnpm typecheck
pnpm build
```
