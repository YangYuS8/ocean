---
title: OpenSpec 历史决策摘要
---

# OpenSpec 历史决策摘要

本页用于替代已删除的根目录 `openspec/`，向人类读者摘要那些仍然有效的历史决策。

## 1. 后端工作区与运行时

历史决策已明确：

- 后端工作区位于 `backend/`
- PHP 运行时配置应归档在 `docker/php/`
- Laravel 是长期后端运行时

## 2. P0 API 与数据库基础

旧 OpenSpec 材料已收敛出以下稳定边界：

- P0 阶段使用 8 张核心 MariaDB 表
- Laravel migration / seeder 是长期初始化主路径
- P0 API 覆盖 dashboard、tasks、samples、results、exceptions、analysis jobs
- 统一响应结构

## 3. 样本工作流与分析任务

历史规范持续强调：

- 样本是长期主业务对象
- 样本详情页是结果、异常和分析任务的中心
- 分析任务需要最小生命周期和重试语义
- 失败后重试应表现为“创建新排队记录，并保留旧失败记录”

## 4. 对旧前端方向的重估

更早的 OpenSpec 决策曾聚焦 Nuxt 工作台，因为那是当时构建初始 MVP 页面壳的可行短期路径。

从长期角度看，更好的工程收敛方向是：

- `Laravel API`
- `React/TypeScript SPA (Vite)`
- `Python Worker`
- `MariaDB`
- `Redis`
- `Nginx`
- `Docker Compose`

这意味着：

- **保留 Laravel 仍然是稳定结论**
- **Nuxt 相关决策应视为阶段性历史，而不是长期主方向**

## 5. 仍然有效的历史约束

以下约束继续成立：

- API 路径和字段语义应尽量稳定
- 状态流转应通过显式动作推进
- 技术栈切换时不要随意改写数据语义
- Python 与 Redis 应继续充当异步边界，而不是被塞回同步事务路径

## 6. 本次文档迁移的结果

经过当前重构后：

- 旧业务设计文档已合并到 `website/docs/`
- OpenSpec 历史决策已沉淀到新的文档站
- 根目录 `docs/` 与 `openspec/` 继续保持删除状态
