FROM docker.io/library/php:8.3-fpm

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN pecl install xdebug \
&& docker-php-ext-enable xdebug

COPY php.ini /usr/local/etc/php/php.ini