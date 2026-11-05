# syntax=docker/dockerfile:1.7
#
# CourtWatch production image (proposal B6.10): FrankenPHP = Caddy (automatic HTTPS) + PHP.
# One image, three roles: "web" (default), "horizon" (queues) and "scheduler" (cron).
# See docker-compose.prod.yml and docs/DEPLOY.md. No secrets are baked in: configuration
# comes from the environment (.env on the server, passed by Docker Compose).

ARG PHP_VERSION=8.3

# ---------------------------------------------------------------- 1. PHP dependencies
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

# ---------------------------------------------------------------- 2. Front-end assets (Vite)
FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
# The Filament theme imports CSS from vendor/filament.
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ---------------------------------------------------------------- 3. Runtime
FROM dunglas/frankenphp:1-php${PHP_VERSION}-bookworm AS app

# PHP extensions; the PostgreSQL 16 client (pg_dump for backups) comes from the PGDG
# repository because Debian's own client is older than the server.
RUN install-php-extensions pdo_pgsql pgsql pdo_mysql intl zip gd bcmath exif pcntl redis opcache \
    && apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl gnupg unzip libcap2-bin \
    && install -d /usr/share/postgresql-common/pgdg \
    && curl -fsSL -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc https://www.postgresql.org/media/keys/ACCC4CF8.asc \
    && echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt bookworm-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends postgresql-client-16 \
    && apt-get purge -y gnupg \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    SERVER_NAME=:80

WORKDIR /app

COPY docker/prod/php.ini $PHP_INI_DIR/conf.d/zz-courtwatch.ini
COPY docker/prod/Caddyfile /etc/frankenphp/Caddyfile
COPY --chmod=755 docker/prod/entrypoint.sh /usr/local/bin/courtwatch

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

# The committed API documentation is copied into the storage volume at start-up
# (storage/ is a volume, so files baked into it would go stale after an update).
RUN php artisan package:discover --ansi \
    && mkdir -p /opt/courtwatch \
    && cp -r storage/app/private/scribe /opt/courtwatch/scribe \
    && mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache /data /config \
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp

USER www-data

EXPOSE 80 443 443/udp

# Web role only (docker-compose.prod.yml disables it for horizon and the scheduler).
HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=3 \
    CMD ["curl", "-fsS", "-o", "/dev/null", "http://127.0.0.1:8080/up"]

ENTRYPOINT ["courtwatch"]
CMD ["web"]
