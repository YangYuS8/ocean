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

当前规则：

- `raw_value` 和 `normalized_value` 使用 JSON 结构
- 新结果记录默认进入 `draft`

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

当前阶段仍允许显式传入：

- `operator_id`
- `entered_by`
- `queued_by`
- `reported_by`
- `resolved_by`

这些都是过渡策略，后续应迁移到 Laravel 认证态注入。

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
