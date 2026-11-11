# syntax=docker/dockerfile:1
FROM dunglas/frankenphp:1-php8.3-bookworm AS base

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && install-php-extensions ctype dom fileinfo mbstring pdo_mysql opcache zip

WORKDIR /app

FROM base AS dependencies
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist --optimize-autoloader

COPY . .
RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --no-dev --classmap-authoritative --no-interaction \
    && composer check-platform-reqs --no-dev

FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
# Tailwind may scan vendor Blade templates. Use the production dependency tree.
COPY --from=dependencies /app /app
RUN npm run build

FROM base AS runtime
COPY --from=dependencies /app /app
COPY --from=frontend /app/public/build /app/public/build
COPY Caddyfile /etc/frankenphp/Caddyfile
RUN mkdir -p /config/caddy /data/caddy \
    && chown -R www-data:www-data storage bootstrap/cache /config/caddy /data/caddy \
    && chmod -R u+rwX,g+rwX storage bootstrap/cache \
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp

ENV PORT=8080 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=mysql \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=sync \
    SESSION_SECURE_COOKIE=true

USER www-data
EXPOSE 8080
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]