# Ocean

海洋生态样本与设备巡检管理系统。一个基于 `Nuxt 4 + PHP + MariaDB + Redis + Python` 的多服务开发环境与 MVP 实现基础。

## 当前技术栈

- 前端：`Vue 3` + `Nuxt 4` + `Nuxt UI Dashboard`
- 前端运行方式：`Node/Nitro`，开发端口 `3000`
- 后端：`PHP-FPM`，通过 Nginx 暴露 `/api/`
- 数据库：`MariaDB 11`
- 队列/异步边界：`Redis`
- 分析环境：`Python 3.12`
- 入口代理：`Nginx`
- 编排方式：`docker-compose`（首选），`podman-compose`（个人习惯或兼容场景可用）

## 当前能力概览

- Nuxt 工作台已提供总览、样本管理、巡检任务、仪器监控、统计报表、系统设置等页面壳
- PHP 已实现 P0 API：
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
- MariaDB 建表与初始化脚本已落地在 `src/database/`

## 目录结构

- `frontend/`：Nuxt 4 前端工程
- `src/`：PHP 应用、P0 API、数据库脚本与执行脚本
- `php/`：PHP-FPM 镜像配置
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

### 2. 启动服务

首选使用 `docker-compose`：

```bash
docker compose up -d --build
```

### 3. 访问地址

- Nginx 统一入口：`http://127.0.0.1:8080`
- 前端工作台：`http://127.0.0.1:3000`
- phpMyAdmin：`http://127.0.0.1:8081`
  - 用户名：`root`
  - 密码：`root`

### 4. 初始化数据库

启动服务后，在 PHP 容器中执行：

```bash
docker compose exec php php /var/www/html/scripts/migrate.php
docker compose exec php php /var/www/html/scripts/seed.php
```

## 常用命令

查看服务状态（docker-compose）：

```bash
docker compose ps
```

查看日志（docker-compose）：

```bash
docker compose logs -f
```

进入 PHP 容器（docker-compose）：

```bash
docker compose exec php sh
```

进入前端容器（docker-compose）：

```bash
docker compose exec frontend sh
```

进入数据库容器（docker-compose）：

```bash
docker compose exec db sh
```

停止服务（docker-compose）：

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

## 后端与数据库

PHP 代码位于 `src/`。

关键文件：

- `src/public/index.php`：HTTP 入口
- `src/app/Http/ApiKernel.php`：P0 API 路由分发
- `src/app/Service/P0ApiService.php`：P0 业务逻辑
- `src/database/schema.sql`：建表脚本
- `src/database/seed.sql`：初始化数据脚本

## 验证示例

查询首页摘要：

```bash
curl -s http://127.0.0.1:8080/api/dashboard/summary
```

查询巡检任务：

```bash
curl -s http://127.0.0.1:8080/api/inspection-tasks
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
