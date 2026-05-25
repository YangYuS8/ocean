---
title: 前端迁移方案
---

# 前端迁移方案

## 为什么需要这份文档

仓库当前仍保留早期 MVP 阶段的 Nuxt/Vue 前端实现，而长期架构方向已经明确为 `Laravel API + React + TypeScript + Vite SPA`。

v1.1.0 的意义，就是在这两者之间搭建可落地桥梁。

## v1.1.0 的当前状态

### 保留过渡运行时

- `frontend/` 继续作为当前可运行前端
- 现有 Compose 与 Nginx 默认配置仍然支持这条路径
- v1.1.0 不进行强制切换

### 引入目标主线

- 新增 `frontend-spa/` 工作区
- 它可构建为静态 SPA
- 通过 `VITE_API_BASE` 读取 API 根路径，默认 `/api`
- 通过 `src/api/client.ts` 集中管理 API 访问边界

## v1.1.0 实际交付内容

v1.1.0 有意交付“基础设施与边界”，而不是“完整业务对等”。

预期产出包括：

1. 一个可构建的 React/Vite SPA 骨架
2. 面向 Laravel 的清晰 API 边界
3. 一个可用于 Nginx 的静态托管示例
4. 一个不会扰动当前 Compose 主路径的容器构建示例

## v1.1.0 的明确非目标

v1.1.0 **不会**：

- 删除 `frontend/`
- 默认替换当前正在运行的 Nuxt 部署路径
- 完成业务工作区 UI
- 把业务规则迁出 Laravel

## 新 SPA 的 API 边界规则

SPA 应将 Laravel 视为以下内容的唯一事实来源：

- 工作流状态流转
- 校验规则
- 审计敏感操作
- 列表与详情数据契约
- 异步任务创建与状态回写

SPA 负责：

- 页面组织
- 用户交互所需的客户端状态
- 展示层逻辑
- 通过类型化或集中式 API client 进行请求编排

## 部署形态

目标生产形态保持为：

```text
Browser
  -> Nginx
      -> frontend-spa/dist
      -> /api/ -> Laravel / PHP
```

这样可以让业务前端以静态资源方式部署，避免为工作台强制引入常驻 Node 运行时。

## 版本化迁移预期

### v1.1.0

- 建立 SPA 骨架
- 验证 API 边界
- 提供部署样例
- 保留当前 Nuxt 运行时

### v1.2.0

- 在 SPA 中实现核心巡检 / 样本 / 结果 / 异常工作区
- 让 SPA 成为核心内部工作台的主要交付载体
- 逐步降低对过渡期 Nuxt 路径的依赖

### v1.3.x 修正

- 默认 Docker Compose 与 Nginx 入口现在服务 `frontend-spa/`，不再服务过渡期 Nuxt 运行时
- `frontend/` 仍保留在仓库中，但仅作为早期阶段参考实现
- 长期默认路径已经切换为 Browser -> Nginx -> React/Vite SPA 静态资源 -> `/api/` Laravel

## 仓库维护指导

- 除非后续版本明确退役，否则将 `frontend/` 作为参考资料保留
- 新的长期工作台前端能力应优先放在 `frontend-spa/`
- 文档、部署样例与架构表述应持续与本迁移方案保持一致
