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

### v1.3.x 前端设计系统修正

- SPA 升级到 React 19，并使用 Mantine 9 作为开源组件系统
- Tailwind CSS 通过官方 Vite 插件接入，用于页面级布局、间距和工具类样式
- `react-i18next` 提供应用内语言切换器与翻译资源边界
- 简体中文是默认运营语言，工作台头部必须提供明确可见的“界面语言”切换器
- UI 方向参考 Mantine dashboard/AppShell 范例，形成克制的海洋运营控制台：灰白页面背景、白色描边表面、石板色 / 藏青文字、青绿色主操作、琥珀 / 红色状态强调，以及紧凑的运营信息密度
- 设计语言统一为：默认 12px 圆角、主要表面 16px 圆角、克制阴影、14-16px 正文、20-22px 面板标题、30-36px 页面标题
- 避免玻璃拟态、径向渐变、装饰性圆形 / 色块、过大的 hero 字体，以及没有文档化依据的一次性颜色和圆角
- 前端仍保持 API 驱动与静态资源部署；采用组件库不能把校验、工作流状态流转或审计敏感规则迁出 Laravel

### v1.3.x 组件化修正

- SPA 不应继续把所有能力堆在一个巨型页面或一个巨型 `App.tsx` 中
- `App.tsx` 只保留组合职责：连接 workspace hook、页面外壳、导航与功能面板
- 可复用展示组件放在 `src/components/workspace/`
- 工作台状态、API 编排、表单状态与派生值放在 `src/features/workspace/`
- 每个业务切片在 `src/features/workspace/panels/` 下拥有独立面板模块
- 默认页面应提供总览、任务、样本、结果、异常、分析的分区导航，而不是把所有表单和列表挤进一个连续页面
- `frontend-spa/` 首选包管理器为 `pnpm`；应提交 `pnpm-lock.yaml`，并使用 `pnpm run build` 验证 SPA

## 仓库维护指导

- 除非后续版本明确退役，否则将 `frontend/` 作为参考资料保留
- 新的长期工作台前端能力应优先放在 `frontend-spa/`
- 文档、部署样例与架构表述应持续与本迁移方案保持一致
- SPA 面向用户的文案应维护在 i18n 资源边界中，避免在组件里继续散落中英混杂的硬编码字符串
- SPA 组件树应保持模块化，后续 v1.3.x 面板扩展不应继续膨胀 `App.tsx`
- Mantine theme tokens 与 Tailwind 工具类必须保持一致，不应在零散 CSS 中创建互相竞争的颜色、圆角或间距系统
