FROM node:20-alpine AS frontend-build

WORKDIR /build/frontend-spa

RUN corepack enable

COPY frontend-spa/package.json frontend-spa/pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

COPY frontend-spa/ ./
RUN pnpm run build

FROM docker.io/library/composer:2 AS backend-vendor

WORKDIR /build/backend

COPY backend/composer.json backend/composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

FROM docker.io/library/php:8.4-fpm AS runtime

ARG DEBIAN_MIRROR=mirrors.ustc.edu.cn

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PHP_OPCACHE_ENABLE=1

WORKDIR /var/www/html

RUN if [ -f /etc/apt/sources.list.d/debian.sources ]; then \
      sed -i "s|deb.debian.org|${DEBIAN_MIRROR}|g; s|security.debian.org|${DEBIAN_MIRROR}|g" /etc/apt/sources.list.d/debian.sources; \
    fi \
    && if [ -f /etc/apt/sources.list ]; then \
      sed -i "s|deb.debian.org|${DEBIAN_MIRROR}|g; s|security.debian.org|${DEBIAN_MIRROR}|g" /etc/apt/sources.list; \
    fi \
    && apt-get update \
    && apt-get install -y --no-install-recommends nginx supervisor libzip-dev \
    && docker-php-ext-install zip mysqli pdo pdo_mysql opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY backend/ /var/www/html/
COPY --from=backend-vendor /build/backend/vendor /var/www/html/vendor
COPY --from=frontend-build /build/frontend-spa/dist /usr/share/nginx/html
COPY docker/app/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/app/supervisord.conf /etc/supervisor/conf.d/ocean-app.conf
COPY docker/app/entrypoint.sh /usr/local/bin/ocean-app-entrypoint

RUN rm -f /etc/nginx/sites-enabled/default \
    && chmod +x /usr/local/bin/ocean-app-entrypoint \
    && rm -f bootstrap/cache/*.php \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["ocean-app-entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/ocean-app.conf"]
