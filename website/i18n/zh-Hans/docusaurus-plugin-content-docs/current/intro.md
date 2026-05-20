---
slug: /intro
title: Ocean 文档总览
---

# Ocean 文档总览

本项目是一个面向**海洋生态样本与设备巡检管理**的内部业务系统。本文档站用于替代仓库根目录旧的 `docs/` 与 `openspec/`，将历史资料整理为一套更适合持续维护和发布的双语文档。

## 当前结论

- **后端必须继续保留 Laravel**。
- 长期推荐主线为：
  - `Laravel API`
  - `React + TypeScript` 单页应用（`Vite`）
  - `Python Worker`
  - `MariaDB`
  - `Redis`
  - `Nginx`
  - `Docker Compose`
- 文档站本身位于 `website/`，使用 `Docusaurus` 构建。

## 为什么重构文档

旧资料原本分散在多个 Markdown 和 OpenSpec 归档中，包括：

- 角色与需求分析
- P0 API 范围与字段草案
- MariaDB 数据模型与状态流转
- Laravel 迁移与运行说明
- OpenSpec 历史提案与决策记录

这类结构在探索期可用，但不利于持续维护、双语发布和新成员理解。新的文档站为项目提供了更清晰的信息架构和统一入口。

## 文档地图

- [技术栈重估](./architecture/tech-stack)
- [系统架构](./architecture/system-architecture)
- [角色与需求](./product/roles-and-requirements)
- [版本化 v1 路线图](./product/v1-roadmap)
- [P0 API](./api/p0-api)
- [数据模型与状态流转](./data/data-model-and-states)
- [部署运维](./operations/deployment)
- [OpenSpec 历史决策摘要](./history/openspec-decisions)

## 维护约定

1. 新的项目文档统一写入 `website/docs/`。
2. 简体中文翻译统一放在 `website/i18n/zh-Hans/docusaurus-plugin-content-docs/current/`。
3. 已删除的根目录 `docs/` 与 `openspec/` 不再恢复为主文档入口。
