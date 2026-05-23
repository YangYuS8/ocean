FROM node:20-alpine AS build
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM nginx:alpine AS runtime
COPY --from=build /app/dist /usr/share/nginx/html

# Provide the default static asset image only.
# API proxying and SPA fallback should be configured through
# nginx/spa-target.conf.example or an environment-specific server config.
