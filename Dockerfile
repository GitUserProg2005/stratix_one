# syntax=docker/dockerfile:1

############################
# Base PHP
############################
FROM php:8.2-fpm-bookworm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    mbstring \
    zip \
    opcache \
    pcntl

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/wix/todo

RUN echo "listen = 0.0.0.0:9000" > /usr/local/etc/php-fpm.d/zz-docker.conf

############################
# Composer
############################
FROM base AS composer

COPY . .

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --prefer-dist \
    --no-scripts

############################
# Final App
############################
FROM base AS app

COPY --from=composer /var/www/wix/todo /var/www/wix/todo

# public/build не в образе — собирай на хосте: npm run build
RUN chown -R www-data:www-data /var/www/wix/todo/storage /var/www/wix/todo/bootstrap/cache \
    && chmod -R 775 /var/www/wix/todo/storage /var/www/wix/todo/bootstrap/cache \
    && rm -f /var/www/wix/todo/bootstrap/cache/packages.php /var/www/wix/todo/bootstrap/cache/services.php

EXPOSE 9000

CMD ["php-fpm", "-F"]
