## 1. Laravel 基础壳与运行环境（MVP）

- [x] 1.1 在现有仓库中建立 Laravel 应用基础结构，并确认其最终收口到 `backend/` 目录布局。
- [x] 1.2 调整 `backend/docker/php/` 镜像、Composer 依赖和环境变量，使 Laravel 可在现有容器环境中启动。
- [x] 1.3 校准 `docker-compose.yml`、Nginx 配置和应用入口，保持 Nuxt 3000 端口与 `/api` 统一入口关系不变。

## 2. 数据库迁移体系替换（MVP）

- [x] 2.1 将当前 `schema.sql` 表达的核心 MariaDB 结构重写为 Laravel migration。
- [x] 2.2 将当前 `seed.sql` 中的角色和基础初始化数据重写为 Laravel seeder。
- [x] 2.3 验证 Laravel migration / seeder 创建出的数据库结构与当前 P0 数据模型口径一致。

## 3. P0 API 路由与控制器迁移（MVP）

- [x] 3.1 在 Laravel 中重建 `dashboard` 路由与控制器，保持 `/api/dashboard/summary` 契约不变。
- [x] 3.2 在 Laravel 中重建 `inspection-tasks` 路由、控制器与状态动作接口。
- [x] 3.3 在 Laravel 中重建 `samples` 和 `sample-results` 路由、控制器与 JSON 结果载荷处理。
- [x] 3.4 在 Laravel 中重建 `exceptions` 和 `analysis-jobs` 路由、控制器与状态约束逻辑。

## 4. 服务层与业务规则迁移（MVP）

- [x] 4.1 按领域拆分当前 `P0ApiService.php` 中的业务逻辑，形成 Laravel 服务层。
- [x] 4.2 将当前请求校验逻辑迁移到 Laravel Request / Validation 体系。
- [x] 4.3 将当前异常与统一响应逻辑迁移到 Laravel 原生异常和 JSON 响应体系，同时保持前端契约稳定。

## 5. 契约对齐与联调验证（MVP）

- [x] 5.1 对照当前基线，验证 Laravel 版 P0 API 的路径、字段、分页和错误结构保持兼容。
- [x] 5.2 验证 Nuxt 首页和关键工作区在 Laravel 迁移后无需改动主流程接口调用即可正常工作。
- [x] 5.3 验证 MariaDB、Redis、Nginx、Nuxt 与 Laravel 之间的容器联通关系未被迁移破坏。

## 6. 旧实现收尾与后续扩展准备（MVP / Post-MVP）

- [x] 6.1 在 Laravel 版本通过对齐验证后，清理旧轻量 PHP 路由、请求、响应与数据库辅助骨架。（MVP）
- [x] 6.2 记录迁移后仍保留的临时策略，如显式传参身份字段，并标注后续认证注入切换点。（MVP）
- [x] 6.3 记录 Redis 队列、认证授权、审计日志和更深的 Eloquent 建模作为迁移后的后续增强任务。（Post-MVP）
