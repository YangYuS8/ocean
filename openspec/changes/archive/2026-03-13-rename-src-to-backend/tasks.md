## 1. 目录迁移

- [x] 1.1 将 Laravel 应用目录从 `src/` 重命名为 `backend/`，并确认历史基线目录与 Laravel 根结构完整迁移。
- [x] 1.2 将仓库根 `php/` 目录迁移到 `backend/docker/php/`，保持 Dockerfile 与 `php.ini` 的内容和用途不变。

## 2. 编排与入口对齐

- [x] 2.1 更新 `docker-compose.yml` 中 PHP 服务的 build 配置，使其基于 `./backend` 作为 context 并引用 `backend/docker/php/Dockerfile`。
- [x] 2.2 更新 `docker-compose.yml` 中 PHP 与 Nginx 的 volume 挂载路径，使两者都基于 `./backend` 挂载 Laravel 应用。
- [x] 2.3 在迁移后的 PHP Dockerfile 中保留当前 PHP 主版本，并加入 Debian 国内源优化以提升 `apt` 构建稳定性。
- [x] 2.4 校正 Nginx 与相关环境约定，确保 Laravel `public/` 入口在新目录结构下仍可访问。

## 3. 文档与规范同步

- [x] 3.1 更新 `README.md` 中关于后端目录、PHP 镜像配置和常用命令的路径说明。
- [x] 3.2 更新 `docs/` 与 `openspec/` 中对 `src/`、`php/` 的结构描述，使其统一切换到 `backend/` 与 `backend/docker/php/`。

## 4. 验证

- [x] 4.1 运行容器构建与启动验证，确认 PHP 镜像可从新路径完成构建。
- [x] 4.2 验证 Debian 软件源优化后 PHP 镜像安装依赖与扩展步骤可稳定完成，且未引入 PHP 主版本降级。
- [x] 4.3 验证 Laravel 基础命令和 Nginx `/api/` 入口在新目录结构下仍能正常工作。
