FROM docker.io/library/node:24-alpine

WORKDIR /app

ARG NPM_REGISTRY=https://registry.npmmirror.com
ENV NPM_CONFIG_REGISTRY=${NPM_REGISTRY}

CMD ["sh", "-c", "npm install && npm run dev -- --host 0.0.0.0 --port 5173"]
