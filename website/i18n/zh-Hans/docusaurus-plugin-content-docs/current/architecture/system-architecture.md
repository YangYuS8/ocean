---
title: 系统架构
---

# 系统架构

## 高层拓扑

```text
Browser
  -> Nginx
      -> React SPA 静态资源（目标主线）
      -> Nuxt 前端运行时（当前过渡流）
      -> Laravel API (/api)
          -> MariaDB
          -> Redis
          -> Python Worker
```

## v1.1 过渡状态

平台当前处于前端迁移期：

- `frontend/` 继续作为当前运行流使用的 Nuxt/Vue 实现
- `frontend-spa/` 在 v1.1.0 中新增，作为目标 React + TypeScript + Vite 基线
- Laravel 继续维持稳定的 `/api` 契约，以支持两条前端线并存
- Nginx 继续作为边界层，可在过渡期承接 Nuxt 路径和目标静态 SPA 路径

## 组件职责

### Laravel API

Laravel 是业务系统中心，负责：

- 暴露 `/api` 接口
- 统一执行业务规则和状态流转
- 管理迁移与初始化
- 作为未来认证、授权、审计和队列编排的接入点

### React / TypeScript SPA

业务前端建议作为独立 SPA 存在，用于：

- 消费 Laravel API
- 承载任务、样本、结果、异常和分析流程
- 输出由 Nginx 托管的静态资源
- 从 `VITE_API_BASE` 读取 API 根路径，默认 `/api`

在 v1.1.0 中，这条 SPA 只交付基础骨架，用于证明目标运行时、API 边界与静态部署形态，而不是立刻替换现有 Nuxt 流。

### Nuxt / Vue 过渡前端

现有 Nuxt 前端在迁移期仍然保留，因为它继续承接当前可运行流程。但从架构定位上，它现在应被视为过渡实现，而不是长期业务前端主线。

### Python Worker

Python 负责分析与算法执行，包括：

- 图像处理
- 自动建议生成
- 模型推理
- `analysis_jobs` 对应的后台执行

### MariaDB

MariaDB 继续作为核心事务库，存储：

- 巡检任务
- 样本
- 样本结果
- 异常
- 分析任务
- 用户与角色

### Redis

Redis 继续作为异步边界，用于：

- 解耦 Laravel 与 Python Worker
- 支撑队列、重试和后续编排

### Nginx

Nginx 继续作为统一入口，负责：

- 代理 Laravel API
- 托管 SPA 构建产物
- 隐藏内部服务拓扑

## 核心业务链路

```text
inspection_tasks
  -> samples
      -> sample_results
      -> exceptions
      -> analysis_jobs
```

## 分析任务链路

```text
用户动作
  -> Laravel 创建 analysis_jobs(queued)
  -> Redis / 受控异步边界
  -> Python Worker 消费任务
  -> Laravel 回写 running / succeeded / failed
```

## 设计原则

1. **后端中心化规则**：状态机和校验留在 Laravel。
2. **关注点分离**：前端消费 API，而不是承担 SSR 运行时职责。
3. **异步执行隔离**：Python 不接管主事务工作流。
4. **部署路径稳定**：Nginx + Docker Compose 继续作为主交付模型。
5. **受控前端迁移**：v1.1 先建立并存与部署边界，v1.2 再把核心工作区交付迁移到 SPA。
