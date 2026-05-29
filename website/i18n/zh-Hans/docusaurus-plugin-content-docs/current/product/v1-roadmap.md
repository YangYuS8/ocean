---
title: v1 路线图
---

# v1 路线图

本路线图将 v1 主线细化为一组语义化版本，便于团队更精确地管理交付范围、验收和依赖关系。

## v1.0.0 — 架构基线 / 契约冻结

### 目标

锁定后续 v1 工作所依赖的后端、数据与 API 基线。

### 范围

- 确认 Laravel 为长期后端运行时
- 冻结 P0 API 范围与响应约定
- 冻结核心 MariaDB 实体语义与状态流转
- 将项目文档统一收敛到 Docusaurus 站点

### 主要交付物

- 基于 Laravel 的 P0 API 契约基线
- 已文档化的数据模型与状态机基线
- 以英文为默认、简体中文为 i18n 的 Docusaurus 文档站
- 面向 GitHub Pages 的文档发布工作流

### 退出标准 / 验收标准

- 团队对长期技术栈方向达成一致
- P0 API 和状态语义集中沉淀到一个稳定入口
- `website/` 可成功构建并成为文档事实来源
- 已冻结统一列表响应 `meta`，包括 `GET /api/samples/{id}/results`
- sample 在 P0 下的结果接收状态推进由后端显式负责
- 初始化数据包含一条可幂等验证的核心链路样例

## v1.1.0 — 前端迁移基础

### 目标

为业务前端向 Laravel API + React/TypeScript SPA 主线迁移做好准备。

### 范围

- 定义从当前前端阶段过渡到 SPA 主线的目标
- 明确新前端所需的 API 消费边界
- 让部署假设继续与 Nginx 和 Compose 对齐
- 交付一个最小 React/Vite 工作台骨架，但不替换当前 Nuxt 运行时

### 主要交付物

- 已文档化的前端迁移基线
- `frontend-spa/` React + TypeScript + Vite 骨架
- 面向新 SPA 的 API 集成假设
- 通过 Nginx 托管静态资源的部署说明
- 不破坏当前 Compose 主线的目标静态构建与容器示例

### 退出标准 / 验收标准

- 交付团队对前端方向没有歧义
- 没有关键产品区域依赖 SSR 特性才能成立
- SPA 目标集成契约已被记录
- 仓库中存在一个可成功构建、且支持 `VITE_API_BASE` 配置的 SPA 基线
- 过渡期内 Nuxt 实现继续可用于当前运行路径

## v1.2.0 — 核心工作区完成

### 目标

围绕巡检任务、样本、结果、异常和摘要视图完成核心内部工作区。

这一版本将不再停留在过渡骨架，而是预期由 React/Vite 前端承担核心工作区的主要交付。

### 范围

- 巡检任务列表、详情与最小动作
- 样本列表、详情与创建流程
- 样本结果录入与查询
- 异常创建与解决
- 首页摘要联通

### 主要交付物

- 可用于日常 MVP 操作的内部工作区
- 与 Laravel API 打通的 P0 UI 流程
- 稳定的任务、样本、结果与异常处理链路

### 退出标准 / 验收标准

- 用户可以在不依赖纯占位页面的情况下完成 P0 主链路
- 核心列表/详情/动作流程已对接真实 API
- 核心页面的空态和失败态已处理

### v1.3.x 修正

默认 Compose/Nginx 运行时现在服务 React/Vite SPA，补齐 v1.2.0 预期的实际前端切换。旧 Nuxt 实现仍作为仓库参考材料保留，但不再是默认运行时。

## v1.3.0 — 异步分析闭环

### 目标

让分析任务从静态记录升级为真实、可追踪的异步执行闭环。

### 范围

- 创建并跟踪分析任务
- 保留 Redis 作为异步边界
- 使用 Python Worker 消费排队任务
- 回写成功、失败、重试和结果摘要状态

### 主要交付物

- 可工作的 `analysis_jobs` 生命周期闭环
- 支持任务类型的 Python Worker 执行路径
- 保留失败历史并创建新排队任务的重试语义
- 对运行中和失败分析任务的运维可见性

### 退出标准 / 验收标准

- 分析任务可按定义生命周期推进并留下可追踪状态记录
- 失败任务可重试且不会篡改历史失败记录
- 用户在任务结束后能看到有效状态与下一步指引

### v1.3.0 实现说明

v1.3 线路以 MariaDB 中的 `analysis_jobs` 作为持久事实来源，并以 Redis 作为 Worker 交接边界。Laravel 写入任务记录后，会把精简任务载荷推入 `ANALYSIS_JOB_REDIS_QUEUE`；Python Worker 先消费该队列，再调用 Laravel API 将任务推进到 running、succeeded 或 failed。为兼容旧环境，Worker 仍保留 HTTP 排队列表作为兜底路径。

## v1.3.x — 前端可用性与组件化补齐

### 目标

把默认 SPA 从“已打通的 MVP 工作台”推进为更可维护的 React 19 运营控制台。

### 范围

- 在工作台头部提供明确可见的语言切换入口
- 用总览、任务、样本、结果、异常、分析的分区导航替代单一长页面
- 将前端拆分为可复用工作台组件、功能面板和专用 workspace 状态 / API hook
- 将 `frontend-spa/` 的本地开发与容器构建命令统一到 `pnpm`
- 用户可见 SPA 文案只维护在 i18n 资源边界中
- 围绕 Mantine theme tokens 与 Tailwind 工具类统一视觉语言，而不是继续散落一次性 CSS

### 主要交付物

- `App.tsx` 收敛为组合层，不再承载业务逻辑和表单编排
- `src/components/workspace/` 下的共享组件
- `src/features/workspace/` 下的工作台功能代码
- 提交 `pnpm-lock.yaml`，保证 SPA 安装可复现
- Docker 构建路径使用 `pnpm install --frozen-lockfile` 和 `pnpm run build`
- 文档化前端视觉标准，包括字号层级、颜色、圆角、阴影、布局密度和应避免的反模式

### 退出标准 / 验收标准

- 用户无需在密集控件中寻找，就能看到中文 / 英文切换入口
- 每个主要工作台能力都能通过清晰的分区标签进入
- 后续 v1.3.x 工作台功能不应再通过扩写 `App.tsx` 大块代码实现
- `frontend-spa/` 中 `pnpm run build` 成功
- 英文与简体中文文档保持同步
- 新增 SPA UI 工作遵循已文档化的 Mantine/Tailwind 设计语言，不再引入临时渐变、过大字号或不一致圆角

## v1.4.0 — 治理与运维

### 目标

补齐一个更可靠内部系统所需的治理与运行控制能力。

### 范围

- 认证与身份注入策略
- 基础 RBAC
- 审计轨迹增强
- 运维运行手册与验证规范

### 主要交付物

- 可落地的认证方向文档
- 基础角色权限模型
- 对敏感操作更清晰的审计要求
- 更完善的部署与维护指导

### 退出标准 / 验收标准

- 系统有明确路径逐步摆脱手工传入身份字段
- 高价值操作具备定义清晰的审计要求
- 运维人员拥有稳定的常用运行文档

### v1.4.0 实现说明

v1.4 线路通过 `POST /api/auth/login` 引入 SPA token 登录，为高价值写操作路由增加基础 RBAC 权限控制，并通过 `audit_events` 表记录任务、样本、结果、异常和分析作业动作。旧 payload 身份字段在过渡期仍作为归因兼容保留，但用户发起的受保护写操作需要 `Authorization: Bearer <token>`。`X-Ocean-Actor-Id` 仅作为非 SPA 工具的内部过渡桥接保留，Python Worker 在引入更强 Worker 凭证前使用独立内部桥接。

## v1.4.1 — 设置与用户管理收敛

### 目标

在继续扩展治理能力之前，将分散的运行偏好和用户身份管理收敛为 SPA 中的一等页面。

### 范围

- 增加设置页，作为当前散落在工作台外壳和功能面板中的工作区级选项的统一入口
- 增加用户页，供管理员查看、创建、编辑、启用/停用内部用户，并检查角色分配
- 增加当前用户的个人资料路径，让用户查看自己的账户信息并修改安全的自助字段
- 用户敏感变更继续由 Laravel 负责 RBAC、校验和审计，而不是只做前端控制
- 开发前先确定哪些偏好属于本地 UI 偏好，哪些需要后端持久化

### 主要交付物

- SPA 中的设置与用户管理导航入口
- 已文档化的设置页信息架构，包括语言、显示、工作台行为，以及未来通知、导入/导出等占位范围
- 已文档化的用户管理 API 契约，覆盖管理员管理用户更新和当前用户个人资料更新
- 由基础 RBAC 保护的后端用户管理端点
- 对角色变更、启用状态变更、密码重置等敏感用户管理动作补充审计事件（在对应动作实现时）

### 退出标准 / 验收标准

- 语言和其他工作区偏好不再隐藏在无关控件里
- 管理员拥有一个清晰的用户管理页面，而不是依赖 seed 数据或临时后端改动
- 非管理员用户可以查看自己的身份和个人资料，但不会获得管理员级用户管理权限
- 所有用户变更都由 Laravel 校验和授权
- 在标记实现完成前，英文与简体中文文档已说明设置页和用户管理范围

### v1.4.1 实现说明

v1.4.1 将设置与用户管理作为 SPA 中的一等标签页交付。设置页整合当前用户资料编辑、语言偏好、显示密度、默认工作区标签页，以及运行时 / 认证摘要。用户页为管理员提供统一入口，用于用户列表、筛选、创建、资料 / 状态 / 密码编辑、角色替换、启用与停用。所有变更都由 Laravel 通过 token 认证 API 承担，用户偏好持久化在 `user_preferences`，并为用户管理、个人资料更新和设置更新记录审计事件。非管理员用户可以更新自己的资料和设置，但不能访问管理员级用户管理。

## v1.4.2 — 生产应用镜像与 Worker 命名

### 目标

通过将主体 Web 应用打包为一个可部署镜像来简化生产部署，并让服务命名体现产品职责而不是实现语言。

### 范围

- 增加生产用 `ocean-app` 镜像，将 Nginx、PHP-FPM、Laravel API 代码、Composer 生产依赖和构建后的 React/Vite SPA 合并到同一应用镜像
- MariaDB 与 Redis 继续作为独立有状态基础设施服务
- 异步分析 Worker 继续作为独立运行时服务，但将当前 `python` 服务重命名为体现职责的名称，例如 `analysis-worker`
- 让分析 Worker 镜像更接近生产自包含形态，复制 Worker 源码和必要模型 / 运行时资产，而不是依赖开发期 bind mount
- 保留 Laravel 与分析 Worker 之间的 Redis 边界
- 定义生产 Compose profile 或生产 Compose 文件，使用合并后的 app 镜像，同时保持本地开发体验清晰

### 主要交付物

- `docker/app/Dockerfile` 或等价的 Laravel + SPA 多阶段生产 Dockerfile
- 生产 Nginx 配置：直接服务 SPA 静态资源，并将 `/api/` 通过本地 PHP-FPM 路由到 Laravel `public/index.php`
- 用于在同一 app 容器中运行 Nginx 与 PHP-FPM 的 entrypoint 或 supervisor 配置
- Compose 调整：引入生产 `app` 服务，并将面向 Worker 的服务名从 `python` 改为 `analysis-worker`
- 生产就绪的分析 Worker 镜像路径；如果模型打包仍需外部化，则明确记录为后续项
- 更新运维文档，覆盖迁移执行、存储卷、Worker API 基址和重命名后的服务

### 退出标准 / 验收标准

- 生产部署可以从一个不可变镜像运行主体 Web 应用，不再 bind mount `backend/` 或 `frontend-spa/`
- 生产拓扑从 `frontend + nginx + php + db + redis + python` 收敛为 `app + db + redis + analysis-worker`
- 上传 / public storage 仍可持久化，并能按需与分析 Worker 共享
- Worker 服务名能表达它在产品链路中的职责，而不是实现语言
- `docker compose config` 和相关 app 镜像构建命令成功
- 现有后端、SPA 与文档验证命令继续通过

### v1.4.2 实现说明

v1.4.2 交付生产打包基线。`docker/compose.prod.yml` 引入由 `docker/app/Dockerfile` 构建的 `app` 服务：该镜像会构建 React/Vite SPA、安装 Laravel 生产 Composer 依赖、通过 Nginx 服务 SPA 静态资源，并将 `/api/` 路由到同容器内的 PHP-FPM。默认与生产 Compose 路径都使用 `analysis-worker` 作为异步模型 / 推理执行服务名。analysis-worker 镜像在构建时复制 Worker 代码和模型资产，满足生产使用。MariaDB 与 Redis 继续作为独立服务，迁移继续作为显式部署步骤，除非明确启用 `OCEAN_RUN_MIGRATIONS=true`。

## v1.4.3 — 仓库结构规范化

### 目标

在 React/Vite SPA 与 `analysis-worker` 已成为默认运行路径后，让仓库布局与产品架构一致，在 v1.5 发布加固前清理历史命名噪音。

### 范围

- 将 `frontend-spa/` 转正为一等目录名，例如 `frontend/` 或 `web/`，并更新所有 Compose、Docker、文档、Opencode skill、GitHub labeler、Dependabot 和验证命令引用
- 将旧 Nuxt/Vue `frontend/` 从默认仓库结构中退场；如果没有仍需保留的测试或参考文件，则删除它，或只在文档中保留最小归档说明，而不是继续保留完整可运行应用
- 将实现目录 `python/` 重命名为 `analysis-worker/`，让源码树、Docker 服务和产品职责使用同一套词汇
- 将 Docker 与 Compose 资产集中到 `docker/` 下并使用明确命名，例如 `docker/compose.local.yml`、`docker/compose.prod.yml`、`docker/app/`、`docker/analysis-worker/`、`docker/nginx/`
- 在 SPA 已成为默认前端且生产 app 镜像路径已存在后，删除 `docker-compose.spa.example.yml` 等过时部署示例
- 保持根目录入口小而明确：`README.md`、`README.zh-Hans.md`、`.env.example`，以及只有在明显改善本地上手时才保留根级 `docker-compose.yml` 兼容入口
- 更新 ignore 规则，确保重命名目录下的构建产物和本地缓存不会被跟踪

### 主要交付物

- 重命名后的活跃前端目录，并保留 `pnpm-lock.yaml`、Mantine skills 和构建行为
- 删除或归档旧 Nuxt/Vue 参考前端，同时清理过期 package lock、测试、Dockerfile、CI 与依赖配置引用
- 重命名后的 `analysis-worker/` 源码目录，并更新 Dockerfile、Compose、Worker 存储路径和模型路径文档
- 由 `docker/` 统一承载 app 镜像配置、worker 镜像配置、Nginx 配置和具有清晰 local/production 命名的 Compose 文件
- 如果文档或工作流已不再引用，则删除 `docker-compose.spa.example.yml`
- 更新英文与简体中文架构、运维、贡献指南、README 和路线图引用

### 退出标准 / 验收标准

- 新贡献者无需了解 Nuxt 到 SPA 的历史迁移，也能从目录名判断当前活跃运行时
- 除历史路线说明外，`frontend-spa/` 与 `python/` 不再作为活跃路径名出现
- 旧 Nuxt/Vue 前端被删除，或明确归档在非活跃运行路径之外
- Docker 相关文件不再分散在仓库根目录、`nginx/`、`backend/docker/`、前端目录和 Worker 目录之间
- 本地与生产 Compose 配置在移动后仍能通过校验
- 后端测试、活跃前端构建、文档构建、app 镜像构建和 analysis-worker 镜像构建继续通过

### v1.4.3 实现说明

v1.4.3 规范活跃仓库布局。React/Vite SPA 转正为 `frontend/`，旧 Nuxt/Vue 实现被删除，Worker 源码移动到 `analysis-worker/`，Docker 相关资产移动到 `docker/` 下，并用明确名称区分本地与生产 Compose 文件。根目录 `docker-compose.yml` 仅作为本地上手兼容 include 保留。

## v1.5.0 — 发布加固

### 目标

加固 v1 主线，使其适合重复发布、团队接手和受控扩展。

### 范围

- 强化验证和回归信心
- 对齐构建、部署与文档预期
- 降低产品、架构和运维文档之间的歧义

### 主要交付物

- 更清晰的发布检查清单和验证要求
- 架构、API、数据与运维文档之间的一致性
- 降低新成员接入和增量发布风险

### 退出标准 / 验收标准

- 发布准备不再依赖分散的口口相传知识
- 架构与交付指导内部一致
- v1 主线稳定到足以继续受控迭代
