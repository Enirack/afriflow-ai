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
        bcmath \
        opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-progress --prefer-dist

COPY . .
RUN composer dump-autoload --optimize \
    && chmod +x docker-entrypoint.sh

ENV APP_ENV=prod
EXPOSE 8000

# docker-entrypoint.sh starts the server itself (binding to $PORT when the
# platform provides one, e.g. Railway), so there's no separate CMD to pass it.
ENTRYPOINT ["./docker-entrypoint.sh"]
