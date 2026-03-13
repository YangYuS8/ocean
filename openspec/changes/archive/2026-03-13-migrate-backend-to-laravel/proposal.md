## 为什么

当前仓库已经具备可运行的轻量 PHP P0 API，但后端正在持续增长认证、权限、审计、Redis 队列、数据库迁移和领域服务拆分等复杂度。继续扩展当前手写骨架会逐步演化出一套自制小框架，长期维护成本和架构一致性风险都会上升，因此现在需要将后端承载层迁移到 Laravel。

## 变更内容

- 将当前轻量 PHP API 承载层迁移到 Laravel，保持 Nuxt、Nginx、MariaDB、Redis 和 Python 的职责边界不变。
- 保留现有 P0 API 契约、数据模型和状态流转语义，避免前端在迁移过程中发生大规模联动重写。
- 将数据库初始化方式从当前 `schema.sql / seed.sql / scripts` 路径迁移到 Laravel migration / seeder 体系。
- 用 Laravel 的路由、请求校验、异常处理、服务组织和后续认证/队列能力替代当前轻量 PHP 骨架。

## 功能 (Capabilities)

### 新增功能
- `backend-laravel-runtime`: 提供基于 Laravel 的后端运行时、迁移工具链和 API 承载能力，以替代当前轻量 PHP 骨架。

### 修改功能
- `p0-api-foundation`: 在不改变 P0 API 契约的前提下，将其后端承载方式迁移到 Laravel，并更新数据库初始化与运行约定。

## 影响

- `backend/` 下的 PHP 入口、路由、请求校验、异常处理和服务组织方式。
- `backend/database/`、数据库初始化脚本以及后续迁移执行方式。
- `backend/docker/php/`、`docker-compose.yml`、Nginx 入口与环境变量配置。
- 后续 Redis 队列、认证和审计能力的接入路径。
