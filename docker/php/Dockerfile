FROM docker.io/library/php:8.4-fpm

ARG DEBIAN_MIRROR=mirrors.ustc.edu.cn

COPY --from=docker.io/library/composer:2 /usr/bin/composer /usr/bin/composer

RUN if [ -f /etc/apt/sources.list.d/debian.sources ]; then \
    sed -i "s|deb.debian.org|${DEBIAN_MIRROR}|g; s|security.debian.org|${DEBIAN_MIRROR}|g" /etc/apt/sources.list.d/debian.sources; \
  fi \
&& if [ -f /etc/apt/sources.list ]; then \
    sed -i "s|deb.debian.org|${DEBIAN_MIRROR}|g; s|security.debian.org|${DEBIAN_MIRROR}|g" /etc/apt/sources.list; \
  fi \
&& apt-get update \
&& apt-get install -y --no-install-recommends git unzip zip libzip-dev \
&& docker-php-ext-install zip mysqli pdo pdo_mysql \
&& pecl install redis \
&& docker-php-ext-enable redis \
&& apt-get clean \
&& rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug \
&& docker-php-ext-enable xdebug

COPY docker/php/php.ini /usr/local/etc/php/php.ini
