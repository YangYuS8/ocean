FROM docker.io/library/node:24-alpine

WORKDIR /app

ARG NPM_REGISTRY=https://registry.npmmirror.com
ENV NPM_CONFIG_REGISTRY=${NPM_REGISTRY}
ENV PNPM_HOME=/pnpm
ENV PATH=${PNPM_HOME}:${PATH}
ENV HOST=0.0.0.0
ENV PORT=3000

RUN corepack enable

CMD ["sh", "-c", "corepack prepare pnpm@10.30.3 --activate && pnpm install --prefer-offline && pnpm dev --host ${HOST} --port ${PORT}"]
