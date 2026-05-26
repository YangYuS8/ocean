---
title: P0 API
---

# P0 API

本文将旧的 MVP API 范围、字段草案与 OpenSpec 要求整合为一个统一契约摘要。

## 统一响应约定

### 成功响应

```json
{
  "data": {}
}
```

### 列表响应

```json
{
  "data": [],
  "meta": {
    "page": 1,
    "page_size": 20,
    "total": 0
  }
}
```

### 错误响应

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "sample_type is required"
  }
}
```

## P0 域与接口

### 治理与审计

- `POST /api/auth/login`
- `GET /api/auth/me`
- `POST /api/auth/logout`
- `GET /api/governance/me`
- `GET /api/governance/roles`
- `GET /api/audit-events`

SPA 现在以 token 登录作为主要认证路径：

```http
POST /api/auth/login
Content-Type: application/json

{
  "username": "admin",
  "password": "password"
}
```

登录成功后返回 bearer token 和 actor 元数据：

```json
{
  "data": {
    "token": "...",
    "actor": {
      "id": 1,
      "username": "admin",
      "display_name": "系统管理员",
      "roles": ["admin"]
    }
  }
}
```

受保护写操作必须发送：

```http
Authorization: Bearer <token>
```

`GET /api/auth/me` 返回当前 token actor。`POST /api/auth/logout` 会撤销当前 token。

v1.4.0 的内部身份桥接使用请求头：

```http
X-Ocean-Actor-Id: 3
```

当该请求头存在时，Laravel 会解析激活状态的 `users` 记录，把 actor 注入请求上下文，并对敏感写操作应用基础 RBAC。v1.4 过渡期仍接受旧的 payload 身份字段作为兜底，以保证旧 Worker 和前端流程继续可用。

`X-Ocean-Actor-Id` 不再是前端主要认证路径，仅作为非 SPA 工具的内部过渡桥接保留。

`GET /api/governance/roles` 返回基础角色与权限目录。`GET /api/audit-events` 返回分页审计事件，并支持 `event_type`、`resource_type`、`resource_id`、`actor_id`、`actor_source` 等筛选条件。

### Dashboard

- `GET /api/dashboard/summary`

最少字段：

- `pending_samples`
- `today_inspection_tasks`
- `open_exceptions`
- `queued_analysis_jobs`

### Inspection Tasks

- `GET /api/inspection-tasks`
- `GET /api/inspection-tasks/{id}`
- `POST /api/inspection-tasks/{id}/start`
- `POST /api/inspection-tasks/{id}/submit`

常见筛选条件：

- `status`
- `assigned_to`
- `task_type`
- `planned_date_from`
- `planned_date_to`
- `keyword`

### Samples

- `GET /api/samples`
- `POST /api/samples`
- `GET /api/samples/{id}`
- `POST /api/samples/{id}/main-image`
- `GET /api/samples/{id}/main-image/content`
- `GET /api/samples/{id}/image-suggestion`

### Sample Results

- `GET /api/samples/{id}/results`
- `POST /api/samples/{id}/results`

v1.0.0 冻结规则：

- `raw_value` 和 `normalized_value` 使用 JSON 结构
- 新结果记录默认进入 `draft`
- `GET /api/samples/{id}/results` 必须遵循统一列表响应，固定返回 `data + meta`
- 创建结果时不得依赖前端驱动样本状态；样本状态规则由后端执行

### Exceptions

- `GET /api/exceptions`
- `POST /api/exceptions`
- `POST /api/exceptions/{id}/resolve`

### Analysis Jobs

- `GET /api/analysis-jobs`
- `POST /api/analysis-jobs`
- `GET /api/analysis-jobs/{id}`
- `POST /api/analysis-jobs/{id}/start`
- `POST /api/analysis-jobs/{id}/succeed`
- `POST /api/analysis-jobs/{id}/fail`
- `POST /api/analysis-jobs/{id}/cancel`
- `POST /api/analysis-jobs/{id}/retry`

## 关键字段与行为约束

### 样本创建

建议必填：

- `sample_code`
- `sample_type`

新样本默认进入 `registered`。

### 过渡期身份字段

v1.4.0 优先使用 token 认证 actor 注入，同时仍允许显式传入以下字段作为兼容兜底：

- `operator_id`
- `entered_by`
- `queued_by`
- `reported_by`
- `resolved_by`

如果 token / header actor 身份与旧 payload 字段同时存在，Laravel 使用认证或注入后的 actor。这些字段仍是过渡策略，后续应从用户发起的 API 契约中移除。

### 基础 RBAC 权限

敏感动作必须提供 header actor 或专用内部 worker 桥接：

| 角色 | 权限 |
| --- | --- |
| `admin` | 所有权限 |
| `inspector` | 开始/提交巡检任务、创建样本、上传样本主图、创建异常 |
| `analyst` | 创建样本结果、创建/解决异常、创建/取消/重试分析作业 |
| `worker` | 开始/成功/失败分析作业 |

用户发起的敏感写操作必须提供 bearer token。旧 payload 身份字段仍用于 actor 归因兼容，但不能单独授权受保护写路由。

Python Worker 状态回调使用内部 worker 桥接：

```http
X-Ocean-Worker: ocean-python-worker
```

该桥接会映射到 seed 中的 `worker01` actor，仅用于内部 Compose / 网络环境，直到后续引入真正的 Worker 凭证。

本地开发的 seed 演示用户为：

| 用户名 | 密码 | 角色 |
| --- | --- | --- |
| `admin` | `password` | `admin` |
| `inspector01` | `password` | `inspector` |
| `analyst01` | `password` | `analyst` |
| `worker01` | `password` | `worker` |

### 审计事件预期

Laravel 会为高价值动作记录审计事件，包括：

- 巡检任务开始与提交
- 样本创建与主图上传
- 样本结果创建
- 异常打开与解决
- 分析作业排队、开始、成功、失败、取消与重试

审计事件包含 `event_type`、`resource_type`、`resource_id`、`actor_id`、`actor_source`、可选 metadata 和 `created_at`。

## 不应阻塞当前交付的 P1 候选

- `POST /api/inspection-tasks`
- `POST /api/inspection-tasks/{id}/complete`
- `PATCH /api/samples/{id}`
- `POST /api/samples/{id}/receive`
- `POST /api/samples/{id}/attachments`
- `POST /api/sample-results/{id}/submit-review`
- `POST /api/sample-results/{id}/approve`
- `POST /api/sample-results/{id}/reject`
- `GET /api/reports/overview`

## 当前明确不属于 P0 范围

- 离线同步
- 地理围栏
- 视频流水线
- 复杂实时设备遥测
- 模型版本管理
- 高级报表编排
- 第三方平台集成
