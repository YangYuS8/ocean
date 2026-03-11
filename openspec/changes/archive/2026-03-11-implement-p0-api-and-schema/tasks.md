## 1. 数据库与初始化（MVP）

- [x] 1.1 按 P0 数据表实现稿创建 MariaDB 迁移或建表脚本，落地 `users`、`roles`、`user_roles`、`inspection_tasks`、`samples`、`sample_results`、`exceptions`、`analysis_jobs`。
- [x] 1.2 添加基础角色初始化数据，并准备最小测试用户或开发联调数据。
- [x] 1.3 验证建表顺序、外键、唯一键、JSON 字段和默认时间字段在当前 MariaDB 环境下可正常创建。

## 2. PHP API 基础骨架（MVP）

- [x] 2.1 在 `src/` 中建立 P0 API 的统一响应结构、路由分发和基础数据库访问层。
- [x] 2.2 实现 `GET /api/dashboard/summary`，返回首页所需的四项聚合摘要数据。
- [x] 2.3 实现 `GET /api/inspection-tasks`、`GET /api/inspection-tasks/{id}`、`POST /api/inspection-tasks/{id}/start`、`POST /api/inspection-tasks/{id}/submit`。
- [x] 2.4 实现 `GET /api/samples`、`POST /api/samples`、`GET /api/samples/{id}`，并完成 `sample_code` 唯一性校验。
- [x] 2.5 实现 `GET /api/samples/{id}/results`、`POST /api/samples/{id}/results`，支持 JSON 结果载荷。
- [x] 2.6 实现 `GET /api/exceptions`、`POST /api/exceptions`、`POST /api/exceptions/{id}/resolve`，并完成异常资源引用校验。
- [x] 2.7 实现 `GET /api/analysis-jobs`、`POST /api/analysis-jobs`、`GET /api/analysis-jobs/{id}`，并落地分析任务记录查询能力。

## 3. 状态流转与业务规则（MVP）

- [x] 3.1 按状态流转草案实现 `inspection_tasks` 的 `assigned -> in_progress -> submitted` 校验逻辑。
- [x] 3.2 在样本与结果相关逻辑中实现 `samples` 的内聚状态推进，避免前端任意修改状态。
- [x] 3.3 以 `sample_results.status` 作为 P0 主流程状态字段，保留 `review_status` 但不作为主判断依据。
- [x] 3.4 实现 `exceptions` 和 `analysis_jobs` 的状态约束，禁止越级或非法状态回退。

## 4. Redis 预留与跨模块联调（MVP）

- [x] 4.1 确认 `analysis_jobs` 与 Redis 的接入边界，至少保证当前实现不阻碍后续队列消费扩展。
- [x] 4.2 完成 Nuxt 前端与 PHP `/api/` 的接口联调验证，确保工作台可从占位数据切换到真实接口。
- [x] 4.3 校验 Nginx、Docker Compose、MariaDB、Redis 与前后端容器的联通关系未因本次实现被破坏。

## 5. 验证与后续扩展准备（MVP / Post-MVP）

- [x] 5.1 编写或整理最小接口验证方式，覆盖创建样本、创建结果、记录异常、创建分析任务和查询摘要等核心路径。
- [x] 5.2 验证显式传参身份字段策略在当前阶段可用，并记录后续切换到认证态注入的接口影响点。（MVP）
- [x] 5.3 记录 `analysis_jobs/retry`、`receive` 动作接口、完整复核流和任务事件表等后续扩展切入点。（Post-MVP）
