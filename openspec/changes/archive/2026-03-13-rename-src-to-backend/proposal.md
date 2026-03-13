## 为什么

当前后端 Laravel 应用承载在 `src/` 目录，而 PHP-FPM 镜像配置单独放在仓库根的 `php/` 目录中。这种目录布局保留了迁移期痕迹，弱化了多服务仓库中后端边界的可读性，也让容器运行时配置与所属应用分离，增加后续维护和文档沟通成本，因此现在需要收敛后端目录结构。

## 变更内容

- 将当前 Laravel 应用目录从 `src/` 重命名为 `backend/`，使后端应用边界与 `frontend/`、`python/` 并列明确。
- 将仓库根的 `php/` 目录并入 `backend/docker/php/`，把 PHP-FPM 镜像与 `php.ini` 归到后端应用上下文中统一管理。
- 保持 PHP 镜像主版本不因 Debian 软件源超时问题而降级，并在新的 PHP 镜像定义中补充 Debian 国内源优化能力。
- 更新 Docker Compose、Nginx、README、OpenSpec 文档及相关路径引用，使开发、构建和运行约定与新目录布局保持一致。
- 保持现有 Nuxt、Nginx、MariaDB、Redis、Python 服务职责和运行拓扑不变，不顺手重构其他基础设施目录。

## 功能 (Capabilities)

### 新增功能
- `backend-workspace-layout`: 定义后端应用目录、PHP 运行时配置位置以及与 Docker Compose/Nginx 的路径协同约定。

### 修改功能

## 影响

- 后端应用源码目录、Laravel 命令入口和相关开发文档路径。
- PHP 镜像基础镜像选择、Debian `apt` 软件源与容器构建稳定性约定。
- `docker-compose.yml` 中 PHP/Nginx 的 build、volume 与工作目录引用。
- Nginx 指向 Laravel `public/` 的根路径与容器挂载约定。
- OpenSpec 与项目文档中关于 `src/`、`php/` 的结构说明。
