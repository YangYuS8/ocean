# Ocean

Ocean is an internal platform for marine ecological sample management and equipment inspection workflows.

## Recommended long-term mainline

- Backend: `Laravel` (must remain)
- Business frontend mainline: `React + TypeScript + Vite` SPA
- Async analysis: `Python Worker`
- Database: `MariaDB`
- Queue / decoupling boundary: `Redis`
- Entry proxy: `Nginx`
- Orchestration: `Docker Compose`
- Documentation site: `Docusaurus` in `website/`

> The old root-level `docs/` and `openspec/` content has been consolidated into the new documentation site and should no longer be restored as the primary documentation entry.

## Documentation entry points

- Site source: `website/`
- Default documentation entry: `website/docs/intro.md`
- Simplified Chinese docs: `website/i18n/zh-Hans/docusaurus-plugin-content-docs/current/`
- GitHub Pages workflow: `.github/workflows/docs-pages.yml`

Recommended starting points:

- `website/docs/intro.md`
- `website/docs/architecture/tech-stack.md`
- `website/docs/architecture/system-architecture.md`
- `website/docs/api/p0-api.md`
- `website/docs/data/data-model-and-states.md`
- `website/docs/operations/deployment.md`

## Why Nuxt is no longer the recommended long-term mainline

This system is primarily an internal management workspace. The engineering focus is on:

- Stable API contracts
- Controlled state transitions
- Repeatable deployment
- Clear Redis / Python async boundaries
- Lower operational complexity

Compared with `Nuxt/Vue + SSR/Nitro + long-running Node runtime`, `Laravel API + React/Vite SPA` is a better fit for the long-term delivery path. See the technology stack reassessment in the docs for the detailed rationale.

## Run and initialize

### Start services

```bash
docker compose up -d --build
```

### Initialize the database

```bash
docker compose exec php php /var/www/html/artisan migrate --seed --force
```

### Common validation commands

```bash
curl -s http://127.0.0.1:8080/api/dashboard/summary
curl -s http://127.0.0.1:8080/api/inspection-tasks
docker exec ocean-php php /var/www/html/artisan route:list --path=api
docker exec ocean-php php /var/www/html/artisan migrate:status
```

## Main directories

- `backend/`: Laravel backend and the legacy lightweight PHP reference baseline
- `frontend/`: existing frontend implementation from the earlier phase
- `frontend-spa/`: React + TypeScript + Vite SPA transition baseline for the long-term frontend mainline
- `python/`: Python worker and model runtime
- `nginx/`: Nginx configuration
- `website/`: bilingual Docusaurus documentation site

## Documentation maintenance rules

1. All future project-level documentation should be maintained under `website/`.
2. Laravel remains the long-term backend runtime.
3. The deleted root `docs/` and `openspec/` directories should not be restored as the primary documentation entry.

## 简体中文

- 简体中文版 README：`README.zh-Hans.md`
- 中文文档入口：`website/i18n/zh-Hans/docusaurus-plugin-content-docs/current/intro.md`
- 构建后的站点可通过语言切换器进入简体中文版本。
