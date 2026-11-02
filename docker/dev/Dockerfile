# Development image used by docker-compose.yml and the Codespaces dev container.
# The source code is bind-mounted at /var/www/html; nothing is baked in.
FROM php:8.3-cli-bookworm

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip curl ca-certificates gnupg postgresql-client sudo \
    && install-php-extensions pdo_pgsql pgsql pdo_mysql intl zip gd pcntl bcmath exif redis opcache \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Non-root user for Codespaces / VS Code (uid 1000 matches the default host user).
RUN groupadd --gid 1000 dev \
    && useradd --uid 1000 --gid 1000 --create-home --shell /bin/bash dev \
    && echo 'dev ALL=(ALL) NOPASSWD:ALL' > /etc/sudoers.d/dev

COPY docker/dev/php.ini /usr/local/etc/php/conf.d/zz-courtwatch.ini
COPY docker/dev/entrypoint.sh /usr/local/bin/dev-entrypoint
RUN chmod +x /usr/local/bin/dev-entrypoint

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PHP_CLI_SERVER_WORKERS=4

WORKDIR /var/www/html
EXPOSE 8000
