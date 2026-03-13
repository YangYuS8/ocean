## 为什么

当前 MVP 已经完成巡检任务执行、样本登记与结果录入，但样本处理链在“发现问题后如何记录并关闭异常”这一环仍然缺位。这使最初冻结的主链 `巡检任务 -> 样本登记 -> 实验结果录入 -> 异常处理 -> 分析状态 -> 首页摘要` 还停在结果之后，无法形成真正可操作、可追踪的处理闭环，因此现在需要将异常处理接入样本详情工作流。

## 变更内容

- 在样本详情页中新增异常区块，支持围绕当前样本查看异常列表、记录异常和解决异常。
- 第一版异常仅挂载在 `sample` 资源上，不在当前 MVP 阶段同时开放 `inspection_task` 或 `sample_result` 级异常入口。
- 为异常录入表单收敛少量固定 `category` 与现有 `severity` 枚举，降低录入门槛并保持后续可扩展性。
- 保持现有 P0 异常接口契约、异常初始 `open` 状态与显式传参身份策略不变，不在本次同步引入独立异常工作区、复杂状态联动或分析任务自动触发。

## 功能 (Capabilities)

### 新增功能
- `sample-exception-workflow`: 定义样本详情页内的异常记录、异常列表和异常解决闭环。

### 修改功能
- `frontend-mvp-operations-workspace`: 将样本详情从“结果处理中心”扩展为同时承接异常处理的 MVP 工作台。
- `p0-api-foundation`: 补充异常前端工作流对 `sample` 资源挂载方式和最小前端闭环的预期行为。

## 影响

- `frontend/app/pages/samples/[id].vue` 及相关前端组件、异常表单与列表刷新逻辑。
- Laravel 现有 `GET /api/exceptions`、`POST /api/exceptions`、`POST /api/exceptions/{id}/resolve` 的前端接入方式。
- 样本详情页的信息层级与后续分析任务入口的页面规划。
