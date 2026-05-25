---
title: 部署运维
---

# 部署运维

## 推荐部署主线

长期部署拓扑应继续收敛到：

```text
Nginx
  -> Laravel API
  -> React SPA 静态资源
MariaDB
Redis
Python Worker
Docker Compose
```

## v1.1 前端迁移部署说明

仓库现在包含两条前端线，职责不同：

- `frontend/`：保留当前 Nuxt/Vue 运行时，用于现行流程
- `frontend-spa/`：目标 React/Vite SPA 基线，用于静态构建产物

v1.1.0 **不会**立刻把默认部署切到 SPA，而是交付后续切换所需的可落地基础：

- 可独立构建、输出到 `frontend-spa/dist` 的 SPA
- 用于托管该目录的 Nginx 示例配置
- 用于静态 SPA 构建的 Compose override / Dockerfile 示例

这样可以在不破坏现有 `docker-compose.yml` 和当前运行方式的前提下，把目标路径固定下来。

## Laravel 运行要求

- Laravel 继续作为统一后端入口
- 数据库初始化使用 migration / seeder
- 旧轻量 PHP 实现仅作为 `backend/legacy-lightweight/` 中的历史参考

## 数据库初始化

服务启动后，推荐执行：

```bash
docker compose exec php php /var/www/html/artisan migrate --seed --force
```

重置数据库可执行：

```bash
docker compose exec php php /var/www/html/artisan migrate:fresh --seed --force
```

## Python 与 Redis 边界

当前运行假设：

- `analysis_jobs` 已落在 MariaDB
- Redis 列表 `ANALYSIS_JOB_REDIS_QUEUE` 是 Worker 的异步交接边界
- Python Worker 处理分析工作负载
- 默认 YOLO 模型路径为 `python/models/uprc2018/best.pt`

运维上可理解为：

1. Laravel 创建并查询任务
2. Laravel 在数据库持久化成功后，将排队任务 id 推入 Redis
3. Python Worker 消费 Redis，执行受支持任务，并通过 Laravel API 回写结果

默认队列名为：

```bash
REDIS_PREFIX=
ANALYSIS_JOB_REDIS_QUEUE=ocean:analysis-jobs:queued
```

该 Worker 交接路径中 `REDIS_PREFIX` 应保持为空，以确保 Laravel 与 Python Worker 读写同一个 Redis 列表名。

如果创建任务时 Redis 短暂不可用，数据库中的持久任务记录仍会保留。运维人员可在 Redis 恢复后重试失败任务或重新入队。

## 常用验证命令

### 首页摘要

```bash
curl -s http://127.0.0.1:8080/api/dashboard/summary
```

### 巡检任务

```bash
curl -s http://127.0.0.1:8080/api/inspection-tasks
```

### Laravel API 路由

```bash
docker exec ocean-php php /var/www/html/artisan route:list --path=api
```

### 迁移状态

```bash
docker exec ocean-php php /var/www/html/artisan migrate:status
```

### 分析队列深度

```bash
docker exec ocean-redis redis-cli LLEN ocean:analysis-jobs:queued
```

## 文档站部署

文档站位于 `website/`，使用 Docusaurus，并独立部署到 GitHub Pages。

交付原则：

- 文档构建与业务服务构建分离
- 文档可以独立发布
- 站点默认语言为英文，简体中文通过 i18n 提供

## SPA 目标静态托管示例

当 React/Vite 线路成为主工作台前端后，推荐托管模式为：

1. 将 `frontend-spa/` 构建为 `frontend-spa/dist`
2. 把 dist 目录挂载或复制到 Nginx 镜像 / 主机目录
3. `/` 通过 SPA fallback 返回 `index.html`
4. `/api/` 反向代理到 Laravel / PHP 入口

本次迁移新增的示例文件包括：

- `nginx/spa-target.conf.example`
- `frontend-spa/Dockerfile`
- `docker-compose.spa.example.yml`

这些文件仅作为目标示例，不会替换当前默认部署。

## 长期不推荐的方向

项目不应继续把 `Nuxt SSR / Nitro` 视作长期部署主线，因为：

- 它对内部管理工作台的收益有限
- 常驻 Node 运行时会增加部署与排障复杂度
- 文档、工作台 UI 与 API 的分层在 SPA + Laravel 下更清晰
