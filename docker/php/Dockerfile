FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip unzip \
    git curl \
    && docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
