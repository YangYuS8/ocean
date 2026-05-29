---
title: 技术栈重估
---

# 技术栈重估

## 已确认的长期约束

### Laravel 必须保留

Laravel 继续作为长期后端运行时，因为它已经承接当前 P0 API 主链路，拥有 migration / seeder 主路径，也比旧的轻量 PHP 基线更适合承载认证、授权、审计、队列和运维能力。

当前已不再是“Laravel 要不要保留”的讨论，而是“围绕 Laravel，其他交付栈如何收敛”。

## 推荐主线

```text
Laravel API
  + React / TypeScript SPA (Vite)
  + analysis-worker (Python)
  + MariaDB
  + Redis
  + Nginx
  + Docker Compose
```

## 为什么推荐 React/Vite SPA 作为业务前端方向

当前项目是一个内部管理系统，不是依赖 SEO 的公网产品站。前端核心诉求主要是：

- 表单、列表与详情页
- 受控状态流转
- 稳定消费 Laravel API
- 简单的容器化部署
- 更低的运行时和排障复杂度

对于这种场景，`React + TypeScript + Vite` 的 SPA 比长期运行的 SSR 应用更合适。

## 为什么 Nuxt/Vue 不适合作为当前长期主线

这并不是否定 Vue 或 Nuxt，而是一个基于项目场景的工程判断。

### 1. SSR 和 Nitro 对当前系统收益有限

系统核心页面包括：

- 巡检任务列表与详情
- 样本工作区
- 结果录入流程
- 异常处理视图
- 分析任务状态跟踪
- 设置与摘要仪表板

这些页面并不依赖 SSR 才能体现主要业务价值。

### 2. 常驻 Node 层会增加部署复杂度

Nuxt 在生产中通常意味着：

- 持续运行的 Node/Nitro
- 更长的代理链路
- Nginx、Node 与 API 多层联动排障

对于内部管理工作台，这些复杂度的回报较低。

### 3. 工程预算应优先投入契约、数据与运维

当前项目最重要的风险点在于：

- 稳定 API 契约
- 受控状态机行为
- 清晰的 MariaDB 数据语义
- 显式的 Redis/analysis-worker 异步边界
- 可重复的 Docker Compose 交付

这些领域比 SSR 基础设施更值得消耗工程复杂度预算。

## Docusaurus 的角色

Docusaurus 只承担文档平台角色，独立放置在 `website/`，不参与业务前端主线。

## 结论

| 领域 | 推荐方向 |
| --- | --- |
| 后端 | Laravel |
| 业务前端 | React + TypeScript + Vite SPA |
| 异步分析 | analysis-worker (Python) + Redis |
| 核心数据库 | MariaDB |
| 反向代理 | Nginx |
| 编排 | Docker Compose |
| 文档站 | Docusaurus |
