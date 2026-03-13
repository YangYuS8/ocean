## 为什么

当前 Laravel 已经接管 P0 API，首页摘要也已接入真实数据，但 `inspections` 与 `samples` 等核心工作区仍停留在占位页面。这导致系统虽然具备后端能力，却尚未形成可演示、可操作的 MVP 前端闭环，因此现在需要优先把巡检任务与样本管理工作区接到真实业务流上。

## 变更内容

- 将 `inspections` 工作区从占位页升级为真实业务页，支持巡检任务列表、详情查看以及 `start` / `submit` 状态推进动作。
- 将 `samples` 工作区从占位页升级为真实业务页，支持样本列表、创建和详情查看。
- 在样本详情中接入样本结果只读列表，作为后续新增结果、异常和分析任务的挂载点，但本次不扩展为完整结果录入工作流。
- 保持现有 P0 API 契约、Nuxt/Nginx 运行方式和 Laravel 后端边界不变，不在本次同步引入认证、权限或 Redis -> Python 异步消费闭环。

## 功能 (Capabilities)

### 新增功能
- `frontend-mvp-operations-workspace`: 定义巡检任务与样本管理工作区的真实前端工作流、页面状态与最小操作闭环。

### 修改功能
- `frontend-admin-workspace`: 将样本管理与巡检任务模块从“占位骨架页”提升为接入真实业务数据与动作的工作区页面。

## 影响

- `frontend/app/pages/inspections.vue`、`frontend/app/pages/samples.vue` 及相关前端组件、数据获取与页面状态管理。
- Laravel 现有 `inspection-tasks`、`samples`、`sample-results` 接口在前端中的接入方式与错误/加载反馈。
- 首页摘要与核心工作区之间的用户路径一致性，以及 MVP 演示的完整性。
