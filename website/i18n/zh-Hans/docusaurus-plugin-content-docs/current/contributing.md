---
title: 贡献者指南
---

# 贡献者指南

本文说明如何在不偏离 v1 架构方向的前提下为 Ocean 做变更。

## 项目方向

Ocean 是一个面向海洋生态样本管理与设备巡检流程的内部平台。

当前 v1 产品主链路是：

```text
巡检任务 -> 样本 -> 结果 -> 异常 -> 分析作业 -> 仪表盘摘要
```

长期运行形态应保持稳定：

- Laravel 负责后端运行时、校验、工作流状态流转、API 契约和审计敏感行为。
- `frontend/` 中的 React 19 + TypeScript + Vite 是默认业务前端。
- MariaDB 是核心事务数据库。
- Redis 是 Laravel 与 analysis-worker 之间的异步交接边界。
- Analysis Worker 聚焦分析、图像处理、模型推理和结果回写。
- Nginx + Docker Compose 是简单可重复的部署目标。
- 详细项目文档统一维护在 `website/`。

## 仓库结构

- `backend/`：Laravel 应用与迁移前轻量 PHP 参考基线。
- `frontend/`：默认 React 19 + TypeScript + Vite SPA，使用 Mantine、Tailwind CSS 与 react-i18next。
- `analysis-worker/`：analysis-worker 实现与模型运行环境。
- `docker/`：Docker Compose、镜像与反向代理配置。
- `website/`：双语 Docusaurus 文档站。

v1.4.3 已规范该布局：活跃 SPA 使用规范前端路径，analysis worker 源码使用 `analysis-worker/` 路径，Docker 相关资产统一放在 `docker/` 下，并以明确名称区分本地与生产 Compose 文件。

不要恢复已删除的根目录 `docs/` 或 `openspec/` 作为主要文档位置。

## 分支与 Pull Request 期望

保持变更小而可审查。一个分支尽量只做一个清晰主题：

- 功能：`feat/...`
- 修复：`fix/...`
- 文档：`docs/...`
- 依赖或工具：`chore/...`

提交 PR 前：

1. 与最新 `main` 同步。
2. 运行与变更相关的检查。
3. 如果行为、架构、路线图、API、运维或设计假设发生变化，同步更新英文与简体中文文档。
4. 在 PR 中说明已运行的验证命令。

## 后端开发规范

Laravel 是业务事实来源。保持以下规则：

- 除非 PR 明确更新文档化契约，否则保持已有 API 契约稳定。
- 校验、状态流转、工作流规则和审计敏感行为应留在 Laravel。
- 数据库变更必须通过 migration 管理。
- 状态机或实体语义变化要同步更新 `website/docs/data/data-model-and-states.md` 及中文对应文档。
- Python 不应绕过定义好的回写 API 直接改变核心业务状态。
- 优先遵循 Laravel 约定和现有项目结构，不随意新增基础目录。

Laravel Boost 已作为项目内开发依赖安装在 `backend/` 下。处理 Laravel 相关任务时，从后端目录运行本地 Artisan 命令。

常用命令：

```bash
cd backend
composer validate --strict
php artisan route:list --path=api
php artisan test --compact
```

如果宿主机 PHP 缺少 SQLite PDO，请在 Docker 中运行后端测试：

```bash
docker compose up -d
docker compose exec -T php php /var/www/html/artisan test --compact
```

## 前端 SPA 开发规范

`frontend/` 是默认业务前端。旧 Nuxt 应用已从活跃仓库结构中移除。

在 `frontend/` 使用 `pnpm`：

```bash
cd frontend
pnpm install
pnpm run build
```

前端架构规则：

- API 访问应集中在 `src/api/client.ts` 或类似的类型化 API 边界中。
- `App.tsx` 应保持为组合层，不应堆积业务逻辑和表单实现。
- 共享工作台 UI 放在 `src/components/workspace/`。
- 工作台状态、API 编排、派生值和功能面板放在 `src/features/workspace/`。
- 面向用户的文案应维护在 i18n 资源边界中，避免在组件里硬编码中英混杂字符串。
- 不要为长期 SPA 引入 SSR-only 假设。

设计系统规则：

- 使用 Mantine 提供可访问组件和 theme tokens。
- 使用 Tailwind CSS 做页面级布局、间距和工具类样式。
- 视觉语言遵循前端迁移方案：灰白页面背景、白色描边表面、石板色 / 藏青文字、青绿色主操作、琥珀 / 红色状态强调、紧凑运营信息密度。
- 圆角保持一致：控件和卡片约 12px，主要表面约 16px。
- 避免玻璃拟态、径向渐变、装饰性圆形 / 色块、过大 hero 字体，以及不属于 theme 的一次性颜色和圆角。

前端 Mantine skills 存放在 `frontend/.opencode/skills/`，并由根目录 `opencode.json` 注册。

## 旧前端规范

旧 Nuxt/Vue 实现已不属于活跃运行时目录。除非 PR 明确说明必要性和迁移影响，否则不要重新引入。

验证命令：

```bash
cd frontend
pnpm install
pnpm run build
```

## Analysis Worker 规范

Analysis Worker 负责分析和推理工作，不负责核心业务流程权威。当前实现位于 `analysis-worker/`，由 Python 编写。

- 使用 Redis 作为异步交接边界。
- 使用 Laravel API 推进任务状态并回写结果。
- Worker 变更应限制在明确的 job 边界内。
- Worker 启动方式、队列名或环境变量变化时，应同步更新运维文档。

## 文档规范

英文是主文档语言。简体中文通过 Docusaurus i18n 维护在：

```text
website/i18n/zh-Hans/docusaurus-plugin-content-docs/current/
```

文档位置：

- 架构：`website/docs/architecture/`
- 产品范围与路线图：`website/docs/product/`
- API 契约：`website/docs/api/`
- 数据模型与状态：`website/docs/data/`
- 运维：`website/docs/operations/`

修改文档站后运行：

```bash
cd website
npm run build
```

保持 `website/package-lock.json` 已提交，以便 GitHub Actions 使用 `npm ci` 可复现构建。

## 验证清单

根据变更范围运行最小相关检查：

| 变更区域 | 最小验证 |
| --- | --- |
| `backend/` Laravel 代码 | `composer validate --strict`，`php artisan test --compact` 或 Docker 等价命令 |
| `frontend/` | `pnpm run build` |
| `website/` | `npm run build` |
| Docker/Nginx 路由 | `docker compose up -d --build`，冒烟测试 `/`、一个 SPA 深链接和 `/api/dashboard/summary` |
| Redis/Python 异步闭环 | 队列 / job 生命周期冒烟测试与相关后端测试 |

发布候选建议运行完整检查：

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

## 依赖更新

依赖 PR 仍需要审查。检查具体变更，运行受影响构建，避免合并与 peer dependency 范围或文档化架构方向冲突的升级。

当前包管理器期望：

- `frontend/`：`pnpm`，提交 `pnpm-lock.yaml`。
- `website/`：`npm`，提交 `package-lock.json`。
- `backend/`：Composer，提交 `composer.lock`。

## 发布期望

发布版本标签前：

1. 确保 `main` 干净且已与 `origin/main` 同步。
2. 审核并合并或明确推迟开放 PR。
3. 运行发布候选验证。
4. 确认英文与简体中文文档同步。
5. 创建并推送 annotated tag，例如 `v1.3.0`。
6. 确认 GitHub release workflow 成功。

Release notes 由发布工作流生成。提交信息应尽量简洁，并按功能保持清晰分组。
