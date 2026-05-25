---
title: 数据模型与状态流转
---

# 数据模型与状态流转

本文整合旧的数据模型实现稿、状态流转草案、SQL 规划说明和相关 OpenSpec 约束。

## 核心表

P0 阶段使用 8 张核心表：

- `users`
- `roles`
- `user_roles`
- `inspection_tasks`
- `samples`
- `sample_results`
- `exceptions`
- `analysis_jobs`

## 数据关系

```text
users ----< inspection_tasks ----< samples ----< sample_results
  |                |                    |            \
  |                |                    |             \-> exceptions
  |                |                    |
  |                |                    \-> analysis_jobs
  |
  \----< user_roles >---- roles
```

`exceptions` 通过 `resource_type + resource_id` 关联任务、样本或结果记录。

## 数据库实现基线

- 目标数据库：`MariaDB`
- 字符集：`utf8mb4`
- 主键策略：`BIGINT UNSIGNED AUTO_INCREMENT`
- 时间字段：`DATETIME`
- 状态字段：`VARCHAR(20)`
- JSON 字段：用于结果内容和分析任务参数/摘要

## 状态机

### inspection_tasks

状态集合：

- `assigned`
- `in_progress`
- `submitted`
- `completed`
- `cancelled`

```text
assigned -> in_progress -> submitted -> completed
    \                             \
     \----------------------------> cancelled
```

当前显式 P0 动作：

- `start`: `assigned -> in_progress`
- `submit`: `in_progress -> submitted`

### samples

状态集合：

- `registered`
- `received`
- `testing`
- `reviewed`
- `archived`
- `invalid`

```text
registered -> received -> testing -> reviewed -> archived
     \            \          \           \
      \            \          \-----------> invalid
       \------------\--------------------> invalid
```

说明：

- `POST /api/samples` 创建时默认 `registered`
- 当前 P0 的样本状态推进由后端服务层规则显式负责
- v1.0.0 P0 明确规则：创建 sample result 时，`registered` 或 `received` 推进到 `testing`
- v1.0.0 P0 明确规则：`invalid` 和 `archived` 状态的样本禁止接收新结果，并返回 `409 INVALID_STATE`

### sample_results

状态集合：

- `draft`
- `submitted`
- `approved`
- `rejected`

```text
draft -> submitted -> approved
             \
              \-> rejected
```

说明：

- 结果创建默认进入 `draft`
- 当前主流程由 `status` 驱动
- `review_status` 为后续扩展预留

### exceptions

状态集合：

- `open`
- `resolved`
- `dismissed`

```text
open -> resolved
  \
   \-> dismissed
```

当前 P0 动作：

- `resolve`: `open -> resolved`

### analysis_jobs

状态集合：

- `queued`
- `running`
- `succeeded`
- `failed`
- `cancelled`

```text
queued -> running -> succeeded
   |         \
   |          \-> failed
   \
    \-> cancelled
```

关键重试规则：

- 重试失败任务不能复活原记录
- 原 `failed` 记录必须保留在历史中
- 重试会创建新的 `queued` 记录
- 每条新的 `queued` 记录都会通过 `ANALYSIS_JOB_REDIS_QUEUE` 配置的 Redis 队列交给 Python Worker
- Redis 队列项只是交接信号，数据库记录仍是持久状态来源

## 初始化与迁移原则

- Laravel migration / seeder 是长期初始化主路径
- 原始 SQL 草案可以保留为参考资料，但不再是主生命周期入口
- 基础角色至少包括 `inspector`、`analyst` 和 `admin`
- baseline seeder 需要提供一条幂等的核心链路样例：`inspection_task + sample + sample_result + exception + analysis_job`
