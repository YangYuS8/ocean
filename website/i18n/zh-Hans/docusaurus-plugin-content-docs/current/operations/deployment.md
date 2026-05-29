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

### 治理 actor 上下文

```bash
curl -s -X POST http://127.0.0.1:8080/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"username":"admin","password":"password"}'
curl -s -H 'X-Ocean-Actor-Id: 3' http://127.0.0.1:8080/api/governance/me
curl -s http://127.0.0.1:8080/api/governance/roles
```

在 v1.4.0 中，SPA 通过 `POST /api/auth/login` 登录，并在受保护写路由中发送 `Authorization: Bearer <token>`。`X-Ocean-Actor-Id` 是内部身份注入桥接，不是公开认证机制；它仅在过渡期作为非 SPA 工具的内部路径保留，用户发起的受保护写操作需要 bearer token。

Python Worker 回调在引入真正 Worker 凭证前使用内部桥接请求头：

```bash
curl -s -H 'X-Ocean-Worker: ocean-python-worker' http://127.0.0.1:8080/api/analysis-jobs
```

### 审计事件

```bash
curl -s http://127.0.0.1:8080/api/audit-events?page_size=20
curl -s 'http://127.0.0.1:8080/api/audit-events?resource_type=analysis_job'
```

可通过审计事件验证任务开始/提交、样本结果创建、异常解决、分析作业生命周期推进等高价值动作。

### 设置与用户管理

以 `admin` 登录后，保存 bearer token 并通过 API 验证 v1.4.1 治理页面：

```bash
TOKEN="$(
  curl -s -X POST http://127.0.0.1:8080/api/auth/login \
    -H 'Content-Type: application/json' \
    -d '{"username":"admin","password":"password"}' \
  | python3 -c 'import json,sys; print(json.load(sys.stdin)["data"]["token"])'
)"

curl -s -H "Authorization: Bearer $TOKEN" http://127.0.0.1:8080/api/profile
curl -s -H "Authorization: Bearer $TOKEN" http://127.0.0.1:8080/api/settings
curl -s -H "Authorization: Bearer $TOKEN" 'http://127.0.0.1:8080/api/users?page_size=20'

curl -s -X PATCH http://127.0.0.1:8080/api/settings \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"language":"zh-Hans","display_density":"comfortable","default_workspace_tab":"settings"}'
```

验证用户、个人资料和设置变更的审计覆盖：

```bash
curl -s 'http://127.0.0.1:8080/api/audit-events?resource_type=user&page_size=20'
```

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

## 默认 SPA 静态托管

默认 Compose 路径现在服务 React/Vite 工作台前端：

1. `frontend` 服务从 `frontend-spa/Dockerfile` 构建
2. SPA 镜像在 `80` 端口提供静态资源，并通过 fallback 返回 `index.html`
3. 顶层 Nginx 将 `/` 代理到 SPA 容器
4. 顶层 Nginx 将 `/api/` 路由到 Laravel / PHP 入口

相关文件包括：

- `nginx/default.conf`
- `frontend-spa/Dockerfile`
- `frontend-spa/nginx.conf`
- `docker-compose.yml`

早期 `frontend/` Nuxt 实现仍保留在仓库中作为参考实现，但不再是默认 Compose/Nginx 运行时。

## 长期不推荐的方向

项目不应继续把 `Nuxt SSR / Nitro` 视作长期部署主线，因为：

- 它对内部管理工作台的收益有限
- 常驻 Node 运行时会增加部署与排障复杂度
- 文档、工作台 UI 与 API 的分层在 SPA + Laravel 下更清晰
