# Ocean

Ocean 是一个面向海洋生态样本管理与设备巡检流程的内部平台。

## 长期推荐主线

- 后端：`Laravel`（必须保留）
- 业务前端主线：`React 19 + TypeScript + Vite` 单页应用，使用 Mantine、Tailwind CSS 与 react-i18next
- 历史前端：`frontend/` 中的 Nuxt/Vue 实现保留为参考材料
- 异步分析：`Python Worker`
- 数据库：`MariaDB`
- 队列 / 解耦边界：`Redis`
- 入口代理：`Nginx`
- 编排方式：`Docker Compose`
- 文档站：`website/` 中的 `Docusaurus`

> 旧的根目录 `docs/` 与 `openspec/` 内容已经整合进新的 Docusaurus 文档站，不应再恢复为主要文档入口。

## 文档入口

- 英文主文档：`website/docs/intro.md`
- 简体中文文档：`website/i18n/zh-Hans/docusaurus-plugin-content-docs/current/intro.md`
- 文档站源码：`website/`
- GitHub Pages 工作流：`.github/workflows/docs-pages.yml`

建议优先阅读：

- `website/i18n/zh-Hans/docusaurus-plugin-content-docs/current/intro.md`
- `website/i18n/zh-Hans/docusaurus-plugin-content-docs/current/architecture/tech-stack.md`
- `website/i18n/zh-Hans/docusaurus-plugin-content-docs/current/architecture/system-architecture.md`
- `website/i18n/zh-Hans/docusaurus-plugin-content-docs/current/architecture/frontend-transition.md`
- `website/i18n/zh-Hans/docusaurus-plugin-content-docs/current/api/p0-api.md`
- `website/i18n/zh-Hans/docusaurus-plugin-content-docs/current/data/data-model-and-states.md`
- `website/i18n/zh-Hans/docusaurus-plugin-content-docs/current/operations/deployment.md`
- `website/i18n/zh-Hans/docusaurus-plugin-content-docs/current/product/v1-roadmap.md`

## 为什么 Nuxt 不再作为长期主线

当前系统主要是内部管理工作台，工程重点在：

- 稳定 API 契约
- 清晰受控的状态流转
- 可重复部署
- Redis / Python 异步边界
- 更低的运维复杂度

相比 `Nuxt/Vue + SSR/Nitro + Node 常驻运行时`，`Laravel API + React/Vite SPA` 更适合作为长期交付路径。默认 Compose/Nginx 运行时现在服务 `frontend-spa/`，Nuxt/Vue 仅保留为参考实现。

## 运行与初始化

### 启动服务

```bash
docker compose up -d --build
```

### 初始化数据库

```bash
docker compose exec php php /var/www/html/artisan migrate --seed --force
```

### 常用验证命令

```bash
curl -s http://127.0.0.1:8080/api/dashboard/summary
curl -s http://127.0.0.1:8080/api/inspection-tasks
docker exec ocean-php php /var/www/html/artisan route:list --path=api
docker exec ocean-php php /var/www/html/artisan migrate:status
```

### 前端 SPA 开发

`frontend-spa/` 首选包管理器为 `pnpm`：

```bash
cd frontend-spa
pnpm install
pnpm run build
```

## 主要目录

- `backend/`：Laravel 后端与迁移前轻量 PHP 参考基线
- `frontend/`：早期阶段的 Nuxt/Vue 前端参考实现
- `frontend-spa/`：默认 Compose/Nginx 运行时服务的 React 19 + TypeScript + Vite SPA 工作台
- `python/`：Python Worker 与模型运行环境
- `nginx/`：Nginx 配置
- `website/`：双语 Docusaurus 文档站

## 文档维护规则

1. 后续项目级文档统一维护在 `website/`。
2. 英文是主文档语言，简体中文通过 Docusaurus i18n 维护。
3. Laravel 继续作为长期后端运行时。
4. 已删除的根目录 `docs/` 与 `openspec/` 不应恢复为主要文档入口。
