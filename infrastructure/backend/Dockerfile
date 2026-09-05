FROM php:8.4-cli-alpine

RUN apk add --no-cache \
        icu-dev \
        postgresql-dev \
        libzip-dev \
        git \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        intl \
        zip \
        opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-progress --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

ENV APP_ENV=prod
EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
