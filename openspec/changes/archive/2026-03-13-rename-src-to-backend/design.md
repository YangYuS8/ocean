## 上下文

当前仓库已经形成明显的多服务工作区结构：`frontend/` 承载 Nuxt 4，Laravel 后端承载在 `src/`，Python 分析环境位于 `python/`，并通过 Docker Compose、Nginx、MariaDB、Redis 共同组成开发运行环境。随着 Laravel 迁移完成，`src/` 目录名已经失去表达力，而仓库根的 `php/` 目录仅保存 PHP-FPM 镜像配置，与后端应用本体分离，导致目录语义与实际职责不匹配。

当前稳定约束包括：

- Nuxt 继续以 Node/Nitro 在 3000 端口运行，并由 Nginx 统一代理。
- Laravel 继续作为 PHP 后端运行时，不改变 API 契约、业务模型与 MariaDB/Redis/Python 边界。
- Docker Compose 仍是首选本地编排方式。
- 本次整理聚焦路径与结构收口，不引入新的基础设施层级，也不同时重组 `frontend/`、`python/`、`nginx/`。
- PHP 镜像当前使用 `php:8.3-fpm`，已观察到的问题是 Debian 默认软件源访问超时，而不是 PHP 主版本兼容性失效。

因此，这次设计本质上是仓库结构与运行时约定的收敛：让后端目录名、镜像配置位置和容器挂载路径表达一致的边界。

## 目标 / 非目标

**目标：**
- 将 Laravel 应用目录从 `src/` 统一调整为 `backend/`。
- 将 PHP-FPM 镜像配置从仓库根 `php/` 收拢到 `backend/docker/php/`。
- 保持 PHP 8.3 主版本不因源超时问题被无谓降级，并为 Debian `apt` 安装流程补充国内源优化。
- 保持 Docker Compose、Nginx、README 与 OpenSpec 对后端路径的引用一致。
- 保持 Nuxt、Nginx、MariaDB、Redis、Python 的职责边界和访问拓扑不变。

**非目标：**
- 不重构 Laravel 应用内部目录结构。
- 不将 Nginx、frontend 或 python 的目录一并迁入新的基础设施层。
- 不改变任何现有 P0 API 路径、响应契约或数据库语义。
- 不在本次整理中顺手引入额外容器、CI 流程或多环境部署模型。
- 不因为构建阶段的软件源超时问题而引入 PHP 主版本降级。

## 决策

### 决策 1：后端应用目录统一命名为 `backend/`
- 选择原因：当前仓库根本质上是多服务工作区，`frontend/`、`python/` 已经按服务命名，而 `src/` 仅表达“源码”而不表达“后端应用”。将 Laravel 根目录改为 `backend/` 能让工作区边界更清晰，也降低后续文档和操作说明中的歧义。
- 方案内容：
  - Laravel 根目录整体从 `src/` 迁移到 `backend/`。
  - 与 Laravel 应用相关的路径引用同步切换到 `backend/`。
- 放弃方案：
  - 保留 `src/`：改动最小，但继续保留迁移期命名包袱。
  - 直接放到仓库根：更像单应用 Laravel 仓库，但会削弱当前多服务工作区的边界清晰度。

### 决策 2：PHP-FPM 镜像配置并入 `backend/docker/php/`
- 选择原因：当前 `php/` 目录只包含 Dockerfile 与 `php.ini`，本质上属于后端运行时配置，而不是独立模块。把它并入 backend 可以让“应用”和“运行时”保持同一语义边界，同时避免 Laravel 根目录被容器文件直接污染。
- 方案内容：
  - 将 `php/Dockerfile` 与 `php/php.ini` 迁移到 `backend/docker/php/`。
  - Docker Compose 的 `build.context` 指向 `./backend`，Dockerfile 路径指向 `docker/php/Dockerfile`，使镜像定义贴近后端应用但仍保留独立子目录。
- 放弃方案：
  - 继续保留仓库根 `php/`：后端边界仍不完整。
  - 直接把 Dockerfile 和 `php.ini` 放进 `backend/` 根：会混淆 Laravel 应用文件和容器运行时文件。

### 决策 3：其余服务目录与入口拓扑保持不变
- 选择原因：本次问题的核心是后端目录收口，而不是统一所有容器文件。若同时迁移 `nginx/`、`frontend/Dockerfile`、`python/Dockerfile`，范围会从“后端结构修正”膨胀为“全仓库基础设施重组”。
- 方案内容：
  - 保持 `frontend/`、`python/`、`nginx/` 目录位置不变。
  - 保持 Nginx 作为统一入口，继续代理前端到 3000 端口，并将 PHP 请求路由到 PHP-FPM。
- 放弃方案：
  - 新建统一 `containers/` 或 `infra/` 目录收纳全部容器文件：长期可行，但超出本次变更边界。

### 决策 4：PHP 镜像维持现有主版本，并通过 Debian 国内源优化解决构建超时
- 选择原因：当前已知现象集中在 `apt-get update` 与 Debian 软件源访问路径，而不是 PHP 8.3 与 Laravel 或扩展本身的不兼容。若直接降级 PHP 版本，会把“网络路径问题”误处理成“运行时版本问题”，反而增加额外回归面。
- 方案内容：
  - 继续以当前 PHP 8.3 FPM 系列作为基础镜像，不在本次变更中主动降级主版本。
  - 在迁移后的 `backend/docker/php/Dockerfile` 中加入 Debian 国内源替换或参数化能力，提升 `apt-get update`、扩展安装和镜像构建稳定性。
  - 将该约定写入设计与任务，作为目录迁移的一部分同步完成。
- 放弃方案：
  - 通过降级到更旧 PHP 镜像规避超时：不能直接解决 Debian 软件源访问问题，且会引入额外兼容性验证成本。

### 决策 5：以“路径替换 + 运行校正”为主，不改变行为契约
- 选择原因：这次变更是结构调整，风险主要来自路径引用断裂，而不是业务逻辑本身。最稳妥的方式是保持服务职责、容器网络、API 契约和数据库语义不变，仅更新目录、挂载和文档引用。
- 方案内容：
  - 更新 Docker Compose 中 PHP 与 Nginx 的构建路径、volume 挂载与工作目录引用。
  - 更新 Nginx `root` 对应的新挂载路径，确保仍指向 Laravel `public/`。
  - 更新 PHP 镜像构建步骤，确保新目录与新软件源约定共同生效。
  - 更新 README、OpenSpec、docs 中与 `src/`、`php/` 相关的说明。
- 放弃方案：
  - 在目录调整时同步重写部署拓扑或 API 运行模式：会引入与当前目标无关的额外风险。

## 风险 / 权衡

- 路径替换遗漏导致容器无法启动或 Nginx 找不到 `public/` → 系统性搜索 `src/` 与 `php/` 的引用，并以容器启动和基础 Artisan 命令作为验证基线。
- Docker Compose 的 `build.context` 与 Dockerfile 相对路径不匹配 → 明确区分“构建上下文”与“Dockerfile 位置”，避免仅移动文件而忽略 `COPY` 语义。
- Debian 软件源替换写死单一镜像站导致后续可移植性下降 → 优先采用可配置参数或至少保持镜像站位置集中、易于调整。
- 文档仍混用旧路径，导致团队认知分裂 → README、迁移文档和当前变更产出物必须同步改到新路径。
- `src/legacy-lightweight/` 一类历史表述在迁移后变得别扭 → 历史基线仍保留在 `backend/legacy-lightweight/` 时，需要同步重述其语义，避免文档继续把旧目录名当作概念。

## Migration Plan

1. 将 Laravel 应用目录整体从 `src/` 调整为 `backend/`。
2. 将 `php/` 目录收拢为 `backend/docker/php/`。
3. 在新的 PHP 镜像定义中保留当前 PHP 主版本，并补充 Debian 国内源优化。
4. 更新 Docker Compose 中 PHP 服务的 build 配置与 PHP/Nginx 服务的 volume 挂载路径。
5. 更新 Nginx 配置中与 Laravel 挂载路径关联的说明或约定，确保仍能指向 `public/`。
6. 更新 README、OpenSpec、docs 等文档中的旧路径引用与 PHP 镜像构建说明。
7. 通过容器构建、Nginx 入口访问与 Laravel 基础命令验证新结构可运行。

回滚策略：
- 若目录迁移后出现路径断裂，可暂时恢复 `src/` 与 `php/` 的原始命名，再逐项核对 Compose、Nginx 与文档引用缺口。

## 开放问题

- `backend/legacy-lightweight/` 的历史基线是否继续长期保留，还是在后续阶段彻底移除。
- Debian 国内源是通过固定镜像站落地，还是通过 build args / 环境变量参数化为可切换策略。
