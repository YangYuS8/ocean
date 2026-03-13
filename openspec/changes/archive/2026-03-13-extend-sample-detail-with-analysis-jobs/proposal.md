## 为什么

当前 MVP 已经完成样本详情页内的结果录入与异常处理，但最初冻结的主链在“分析状态”这一环仍未接到前端真实工作流。虽然后端已具备分析任务创建与查询接口，但前端尚未提供围绕当前样本发起分析任务和查看任务状态的入口，因此现在需要把分析任务工作流接入样本详情页，补上主链的最后一段可视化闭环。

## 变更内容

- 在样本详情页中新增分析任务区块，支持围绕当前样本查看分析任务列表、发起分析任务并查看任务状态。
- 第一版分析任务仅保留极小 `job_type` 集合，优先支持 `quality_assessment` 与 `anomaly_scan`，避免过早进入复杂分析参数体系。
- 第一版分析任务以“提交 + 状态查看”为主，不在本次同步实现独立分析任务工作区、复杂参数编辑器或 Redis/Python 真消费闭环。
- 保持现有 P0 分析任务接口契约、任务初始 `queued` 状态和显式传参身份策略不变。

## 功能 (Capabilities)

### 新增功能
- `sample-analysis-job-workflow`: 定义样本详情页内的分析任务发起与状态查看工作流。

### 修改功能
- `frontend-mvp-operations-workspace`: 将样本详情从“结果 + 异常”处理中心扩展为同时承接分析任务发起与状态查看的 MVP 工作台。
- `p0-api-foundation`: 补充当前 MVP 前端工作流对分析任务发起与状态查看的最小预期行为。

## 影响

- `frontend/app/pages/samples/[id].vue` 及相关前端组件、分析任务表单与列表刷新逻辑。
- Laravel 现有 `GET /api/analysis-jobs`、`POST /api/analysis-jobs`、`GET /api/analysis-jobs/{id}` 的前端接入方式。
- 样本详情页的区块层级与首页摘要中 `queued_analysis_jobs` 指标的可解释性。
