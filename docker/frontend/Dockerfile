FROM node:20-alpine AS build
WORKDIR /app

RUN corepack enable

COPY frontend/package.json frontend/pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

COPY frontend/ ./
RUN pnpm run build

FROM nginx:alpine AS runtime
COPY --from=build /app/dist /usr/share/nginx/html
COPY docker/frontend/nginx.conf /etc/nginx/conf.d/default.conf

# Provide the default static SPA asset image. API proxying remains owned by
# the top-level nginx service, while this image handles SPA history fallback.
