# Ocean

海洋生态样本与设备巡检管理系统。当前仓库基于 `Nuxt 4 + Laravel + MariaDB + Redis + Python` 组织多服务开发环境，后端主运行时已经迁移到 Laravel，并保留迁移前轻量 PHP 实现作为对照基线。

说明：后端当前已经由 Laravel 承载 P0 API，迁移前的轻量 PHP 实现保留在 `backend/legacy-lightweight/` 仅供对照与回归参考。若要理解这两者的关系，优先参考 `docs/2.9-P0-实现与验证说明.md` 与 `docs/3.0-Laravel-迁移计划.md`。

## 当前技术栈

- 前端：`Vue 3` + `Nuxt 4` + `Nuxt UI Dashboard`
- 前端运行方式：`Node/Nitro`，开发端口 `3000`
- 后端：`Laravel`（运行于 `PHP-FPM`），通过 Nginx 暴露 `/api/`
- 数据库：`MariaDB 11`
- 队列/异步边界：`Redis`
- 分析环境：`Python 3.12`
- 入口代理：`Nginx`
- 编排方式：`docker compose`（首选），`podman-compose`（可用，但建议与 Dockerfile 基础镜像保持一致）

## 当前能力概览

- Nuxt 工作台已提供总览、样本管理、巡检任务、仪器监控、统计报表、系统设置等页面壳
- Laravel 已实现 P0 API：
  - `GET /api/dashboard/summary`
  - `GET /api/inspection-tasks`
  - `GET /api/inspection-tasks/{id}`
  - `POST /api/inspection-tasks/{id}/start`
  - `POST /api/inspection-tasks/{id}/submit`
  - `GET /api/samples`
  - `POST /api/samples`
  - `GET /api/samples/{id}`
  - `GET /api/samples/{id}/results`
  - `POST /api/samples/{id}/results`
  - `GET /api/exceptions`
  - `POST /api/exceptions`
  - `POST /api/exceptions/{id}/resolve`
  - `GET /api/analysis-jobs`
  - `POST /api/analysis-jobs`
  - `GET /api/analysis-jobs/{id}`
- MariaDB 建表与初始化主路径已落地在 `backend/database/migrations/` 与 `backend/database/seeders/`

## 目录结构

- `frontend/`：Nuxt 4 前端工程
- `backend/`：Laravel 应用、P0 API、migration / seeder、保留的 legacy 基线与 PHP 运行时配置
- `nginx/`：Nginx 反向代理配置
- `python/`：Python 分析环境
- `mysql/`：MariaDB 本地持久化目录
- `docs/`：项目设计、API、数据表与实现说明
- `openspec/`：OpenSpec 规范、变更与归档记录

## 快速开始

### 1. 准备环境变量

仓库当前直接使用根目录 `.env`。

关键默认值：

- `NGINX_PORT=8080`
- `FRONTEND_PORT=3000`
- `PHPMYADMIN_PORT=8081`
- `DB_PORT=3306`
- `MYSQL_ROOT_PASSWORD=root`
- `MYSQL_DATABASE=test_db`
- `DEBIAN_MIRROR=mirrors.ustc.edu.cn`

### 2. 启动服务

首选使用 Docker Compose：

```bash
docker compose up -d --build
```

如使用 Podman，建议直接使用 `podman-compose`，不要混用 `podman compose` 委托外部 `docker-compose` 的模式。

Python 服务当前使用 `uv` 管理容器内虚拟环境，首次构建或启动时会在 `python/.venv`（容器内对应 `/workspace/.venv`）创建虚拟环境，并根据 `python/pyproject.toml` 与 `python/uv.lock` 同步依赖。

### 3. 访问地址

- Nginx 统一入口：`http://127.0.0.1:8080`
- 前端工作台：`http://127.0.0.1:3000`
- phpMyAdmin：`http://127.0.0.1:8081`
  - 用户名：`root`
  - 密码：`root`

### 4. 初始化数据库

启动服务后，在 PHP 容器中执行 Laravel migration / seeder：

```bash
docker compose exec php php /var/www/html/artisan migrate --seed --force
```

如需重新初始化数据库，可先清空后再执行：

```bash
docker compose exec php php /var/www/html/artisan migrate:fresh --seed --force
```

## 常用命令

查看服务状态：

```bash
docker compose ps
```

查看日志：

```bash
docker compose logs -f
```

进入 PHP 容器：

```bash
docker compose exec php sh
```

进入前端容器：

```bash
docker compose exec frontend sh
```

进入数据库容器：

```bash
docker compose exec db sh
```

停止服务：

```bash
docker compose down
```

## 前端开发

前端目录为 `frontend/`，使用 `pnpm`。

本地开发可直接运行：

```bash
cd frontend
pnpm install
pnpm dev
```

默认端口：`3000`

当前首页已接入真实摘要接口，使用 `NUXT_PUBLIC_API_BASE` 指向后端入口。

前端容器启动后会在容器内执行 `pnpm install && pnpm dev`；本地开发时也可直接在 `frontend/` 目录中运行相同命令。

## 后端与数据库

Laravel 应用位于 `backend/`。

当前 `backend/legacy-lightweight/` 目录中的轻量 PHP API 骨架仅用于保留迁移前基线能力，不再承载主运行流量。

PHP-FPM 镜像配置位于 `backend/docker/php/`，默认通过 `DEBIAN_MIRROR` 构建参数使用国内 Debian 镜像源优化构建稳定性。

后端当前为纯 API 运行方式，不再依赖 Laravel 默认的 Blade + Vite 前端资源构建链。

关键文件：

- `backend/public/index.php`：Laravel HTTP 入口
- `backend/routes/api.php`：P0 API 路由定义
- `backend/app/Http/Controllers/`：P0 API 控制器
- `backend/app/Services/`：按领域拆分后的 Laravel 服务层
- `backend/database/migrations/`：数据库迁移
- `backend/database/seeders/`：初始化数据

## Python 分析环境

Python 分析目录位于 `python/`，当前通过 `uv` 管理虚拟环境与依赖同步。

关键约定：

- 本地虚拟环境路径：`python/.venv`
- 容器内虚拟环境路径：`/workspace/.venv`
- 依赖来源：`python/pyproject.toml` 与 `python/uv.lock`
- `uv` 默认索引已切换到清华源
- `python/.venv` 直接挂载进容器，可复用本地虚拟环境，避免每次启动都重新从空环境装依赖
- `uv` 缓存通过独立 volume 持久化，加快重复构建与重启
- Python 服务启动时会自动执行 `uv sync --frozen --no-dev`

常用命令：

```bash
docker compose exec python sh
docker compose exec python python --version
docker compose exec python uv pip list
docker compose exec python uv sync --frozen --no-dev
```

## 验证示例

查询首页摘要：

```bash
curl -s http://127.0.0.1:8080/api/dashboard/summary
```

查询巡检任务：

```bash
curl -s http://127.0.0.1:8080/api/inspection-tasks
```

查看 Laravel 已注册 API 路由：

```bash
docker exec ocean-php php /var/www/html/artisan route:list --path=api
```

查看数据库迁移状态：

```bash
docker exec ocean-php php /var/www/html/artisan migrate:status
```

创建样本：

```bash
curl -s -X POST http://127.0.0.1:8080/api/samples \
  -H "Content-Type: application/json" \
  -d '{"sample_code":"SP-20260311-001","sample_type":"water","name":"测试水样","collector_id":2}'
```

## 设计文档

如果你想先了解当前系统约束与 P0 设计，优先看：

- `openspec/config.yaml`
- `docs/2.4-MVP-API-范围冻结稿.md`
- `docs/2.5-P0-数据表实现稿.md`
- `docs/2.6-P0-API-字段草案.md`
- `docs/2.7-P0-状态流转草案.md`
- `docs/2.8-P0-MariaDB-建表-SQL-草案.md`
- `docs/2.9-P0-实现与验证说明.md`

## 说明

- 当前项目优先围绕 MVP 闭环推进：巡检任务、样本、结果、异常、分析任务、首页摘要
- `Redis` 与 `Python` 已作为后续异步分析边界保留，但当前不要求完整消费链路全部落地
- 当前阶段接口中的 `operator_id`、`entered_by`、`queued_by` 等字段仍允许显式传参，后续再切换到认证态注入
- 迁移前轻量 PHP 基线仅用于对照与回归参考，新的后端功能应优先继续落在 Laravel 中
