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
- Redis 继续作为异步边界
- Python Worker 处理分析工作负载
- 默认 YOLO 模型路径为 `python/models/uprc2018/best.pt`

运维上可理解为：

1. Laravel 创建并查询任务
2. Redis 保留解耦与队列语义
3. Python Worker 执行并回写结果

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

## 文档站部署

文档站位于 `website/`，使用 Docusaurus，并独立部署到 GitHub Pages。

交付原则：

- 文档构建与业务服务构建分离
- 文档可以独立发布
- 站点默认语言为英文，简体中文通过 i18n 提供

## 长期不推荐的方向

项目不应继续把 `Nuxt SSR / Nitro` 视作长期部署主线，因为：

- 它对内部管理工作台的收益有限
- 常驻 Node 运行时会增加部署与排障复杂度
- 文档、工作台 UI 与 API 的分层在 SPA + Laravel 下更清晰
