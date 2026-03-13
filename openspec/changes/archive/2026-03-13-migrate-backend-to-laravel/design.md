## 上下文

当前仓库已经存在一套可运行的轻量 PHP P0 实现：它可以提供首页摘要、巡检任务、样本、样本结果、异常和分析任务接口，并已经与 Nuxt 前端形成最小联调闭环。但这套结构仍然依赖手写路由分发、自定义请求对象、手动异常处理、自定义数据库脚本和集中式服务类，随着认证、权限、审计、队列和更复杂业务规则的引入，后端复杂度将明显上升。

项目当前的稳定约束已经比较清楚：

- 前端采用 Nuxt 4，以 Node/Nitro 在 3000 端口运行，通过 Nginx 反向代理访问。
- 核心事务数据仍以 MariaDB 为主。
- Redis 作为异步任务边界保留。
- Python 继续承担图像处理、质量评估、统计分析和后续消费者角色。
- P0 API、字段契约和状态流转已经在 `docs/2.4` 到 `docs/2.7` 中冻结。

因此，本次变更的关键不是重构业务模型，而是将后端承载层替换为 Laravel，同时保持前端和业务边界尽量稳定。

## 目标 / 非目标

**目标：**
- 用 Laravel 替代当前轻量 PHP API 骨架。
- 保持现有 P0 API 路径、请求语义、响应语义和状态流转约束尽量不变。
- 将数据库初始化方式迁移到 Laravel migration / seeder。
- 为后续认证、权限、审计和 Redis 队列能力打基础。
- 保持 Nuxt、Nginx、MariaDB、Redis、Python 的职责边界和总体部署模型不被推翻。

**非目标：**
- 不在本次迁移中重写 Nuxt 前端信息架构。
- 不在本次迁移中顺手大改业务表语义或状态值。
- 不要求本次迁移同步完成完整 Redis -> Python 消费闭环。
- 不在迁移期间同时实现高级报表、对象存储整合或复杂设备监控。

## 决策

### 决策 1：一次性迁移 Laravel，但冻结现有 API 契约
- 选择原因：团队已经接受 Laravel 作为长期方向，但前端和业务文档已经围绕现有 P0 API 形成稳定边界。如果在迁框架时同时改路径、字段或状态，会把迁移风险放大为“架构迁移 + 业务协议重构”的双重风险。
- 方案内容：
  - 保持 `/api/dashboard/summary`、`/api/inspection-tasks`、`/api/samples`、`/api/exceptions`、`/api/analysis-jobs` 等现有路径不变。
  - 尽量保持分页结构、错误结构和状态值不变。
- 放弃方案：
  - 在迁移 Laravel 时顺手重构 API 契约：会显著增加前后端联调成本。

### 决策 2：保留现有 MariaDB 业务表语义，重写迁移工具链
- 选择原因：当前数据模型已经通过探索阶段收敛并形成文档，不应因为框架迁移而同时推翻。
- 方案内容：
  - 以现有 `users`、`roles`、`user_roles`、`inspection_tasks`、`samples`、`sample_results`、`exceptions`、`analysis_jobs` 为基础。
  - 用 Laravel migration / seeder 重写当前 `schema.sql`、`seed.sql` 和脚本执行方式。
- 放弃方案：
  - 框架迁移时同步重命名表、改字段、改状态语义：范围过大，容易破坏回归验证。

### 决策 3：按领域拆分服务层，而不是继续保留单一 `P0ApiService`
- 选择原因：当前 `P0ApiService.php` 集中承载多个域逻辑，迁移到 Laravel 后若继续保持单一服务类，会把原有集中式复杂度搬进新框架。
- 方案内容：
  - 将业务逻辑按 `dashboard`、`inspection_tasks`、`samples`、`sample_results`、`exceptions`、`analysis_jobs` 等域拆分。
  - 使用 Laravel Controller + Request + Service 的组合组织后端代码。
- 放弃方案：
  - 原样保留集中式服务：会削弱 Laravel 迁移后的长期收益。

### 决策 4：迁移初期优先使用 Laravel 原生路由、验证和异常体系，数据访问可先以 Query Builder 为主
- 选择原因：一次性迁移时最重要的是降低行为偏差，而不是同时强推 ORM 风格重构。Query Builder 更接近当前已有 SQL 和表结构思维，便于平滑承接现有逻辑。
- 方案内容：
  - 路由迁移到 `routes/api.php`
  - 请求校验迁移到 Laravel Request / Validation
  - 异常迁移到 Laravel Exception 体系
  - 初期数据访问优先使用 Query Builder，后续再根据需要逐步引入更深的 Eloquent 建模
- 放弃方案：
  - 在第一次迁移中同时彻底 Eloquent 化：会放大迁移成本和行为偏差风险。

### 决策 5：保留现有 Nuxt / Nginx 拓扑，不把迁移扩展为部署模型重构
- 选择原因：前端已经围绕 Nginx -> Nuxt 3000 和 `/api` 统一入口形成稳定访问方式，部署侧不应成为本次迁移的额外变量。
- 方案内容：
  - 继续由 Nginx 作为统一入口。
  - 保持 Nuxt 走 3000 端口。
  - 保持 MariaDB、Redis、Python 服务角色不变。
  - 调整 `backend/docker/php/` 镜像和容器命令以适配 Laravel 所需环境。
- 放弃方案：
  - 迁移时同时改入口拓扑或前端部署方式：会引入无必要的部署不确定性。

## 风险 / 权衡

- 一次性迁移会替换大部分现有 PHP 基础设施层 → 通过冻结 API 契约和数据模型来降低业务层偏差。
- Laravel migration 与当前 SQL 语义可能出现差异 → 需要用现有 `schema.sql` 和接口验证路径作为对照基线。
- 当前显式传参身份字段不是真正的长期安全模型 → 迁移阶段先保留语义，后续再逐步引入认证注入。
- Query Builder 过渡期不如完整 Eloquent 优雅 → 这是为了先降低迁移风险，后续仍可逐步增强领域建模。
- Redis / Python 消费链路本次不完全重做 → 先保留边界，确保 Laravel 不阻碍后续异步能力接入。

## Migration Plan

1. 在现有仓库中建立 Laravel 基础壳、目录结构和运行入口。
2. 将当前 `.env`、容器和 Nginx 配置调整到支持 Laravel 运行。
3. 用 Laravel migration / seeder 重写当前数据库初始化脚本。
4. 将现有 P0 API 路由与控制器迁入 Laravel，保持契约不变。
5. 按领域拆分服务层并迁移现有业务规则。
6. 用现有 curl 验证路径与 Nuxt 首页联调行为对新实现进行对照校验。
7. 确认 Laravel 版本可替代当前轻量实现后，再清理旧骨架文件。

回滚策略：
- 迁移过程中应保留当前轻量 PHP 基线，直到 Laravel 版本完成契约对齐并通过联调验证。
- 若迁移阶段发现某域行为与现有前端契约不一致，应优先修正 Laravel 实现，而不是修改前端配合后端偏差。

## 开放问题

- Laravel 目录已收口到 `backend/`，迁移前轻量 PHP 基线保留在 `backend/legacy-lightweight/` 供对照参考。
- 是否在第一次迁移中同步引入 Laravel 原生认证脚手架，还是先仅完成 API 承载层替换，仍可在实现时再定。
- 当前 `Exception` 相关模型与命名在 Laravel 中是否需要更明确的类名区分（例如避免与框架异常语义混淆），实现时需要细化。
