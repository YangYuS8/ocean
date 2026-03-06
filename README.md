# 海洋样本巡检系统开发环境模板（LNMP + React + Python）

这是一个用于“海洋样本巡检系统”的初始化开发环境模板，采用开源成熟技术栈：

- 前端：React + Vite + Ant Design
- 后端：PHP-FPM（Nginx 反向代理）
- 数据库：MariaDB 11 + phpMyAdmin
- 科学计算：Python（NumPy / Pandas / SciPy / scikit-learn / Matplotlib）

## 目录结构

- `docker-compose.yml`：服务编排
- `frontend/`：React + Vite 前端工程（开发服务器）
- `php/`：PHP-FPM 镜像与运行配置
- `nginx/`：Nginx 配置（转发到 PHP）
- `src/`：PHP 代码目录（当前包含初始化入口）
- `python/`：Python 科学计算环境与依赖
- `mysql/`：MariaDB 本地持久化目录（已忽略版本管理，仅保留 `.gitkeep`）

## 快速开始

### 1) 启动服务

```bash
docker compose up -d --build
```

### 2) 访问地址

- 网站首页：http://localhost:8080
- 前端开发服务：http://localhost:5173
- phpMyAdmin：http://localhost:8081
  - 用户名：`root`
  - 密码：`root`

### 3) 停止服务

```bash
docker compose down
```

## 说明

- 数据库默认信息（见 `docker-compose.yml`）：
  - 数据库名：`test_db`
  - Root 密码：`root`
- 当前仅完成开发环境初始化与占位文件，不包含具体业务实现。

## 国内源与 `.env` 使用说明

### 1) 初始化环境变量文件

首次使用请复制模板：

```bash
cp .env.example .env
```

`docker-compose.yml` 中的端口、数据库账号和下载源参数均支持从 `.env` 读取。

### 2) 默认国内下载源（已在 `.env.example` 提供）

- `NPM_REGISTRY=https://registry.npmmirror.com`
- `PIP_INDEX_URL=https://pypi.tuna.tsinghua.edu.cn/simple`
- `PIP_TRUSTED_HOST=pypi.tuna.tsinghua.edu.cn`

以上配置用于加速中国大陆网络环境下的依赖安装。

### 3) 切换为官方源（可选）

如需使用官方源，可在 `.env` 中改为：

```env
NPM_REGISTRY=https://registry.npmjs.org
PIP_INDEX_URL=https://pypi.org/simple
PIP_TRUSTED_HOST=pypi.org
```

修改后建议重新构建相关镜像：

```bash
docker compose up -d --build frontend python
```

## 常用命令

进入 PHP 容器：

```bash
docker compose exec php sh
```

进入前端容器：

```bash
docker compose exec frontend sh
```

进入 Python 容器：

```bash
docker compose exec python sh
```

查看服务日志：

```bash
docker compose logs -f
```
