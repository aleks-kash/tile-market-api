FROM php:8.3-fpm-alpine

RUN apk add --no-cache $PHPIZE_DEPS icu-dev postgresql-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql sockets intl \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www/html
