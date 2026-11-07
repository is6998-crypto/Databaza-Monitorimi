#!/usr/bin/env bash
# Development entrypoint: installs dependencies, prepares .env, migrates and seeds,
# then starts the requested process. Safe to run repeatedly (idempotent).
set -euo pipefail

cd /var/www/html
role="${1:-serve}"

wait_for_db() {
    echo "[dev] waiting for PostgreSQL at ${DB_HOST:-postgres}..."
    until pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-courtwatch}" >/dev/null 2>&1; do
        sleep 2
    done
}

prepare_app() {
    git config --global --add safe.directory /var/www/html || true

    if [ ! -f .env ]; then
        echo "[dev] creating .env from .env.example"
        cp .env.example .env
    fi

    echo "[dev] composer install"
    composer install --no-interaction --prefer-dist --no-progress

    if ! grep -q '^APP_KEY=base64:' .env; then
        php artisan key:generate --force
    fi

    if ! grep -q '^BLIND_INDEX_KEY=[0-9a-f]\{64\}' .env; then
        key="$(php -r 'echo bin2hex(random_bytes(32));')"
        sed -i "s/^BLIND_INDEX_KEY=.*/BLIND_INDEX_KEY=${key}/" .env
    fi

    # GitHub Codespaces forwards the app through a proxy that terminates HTTPS: trust its
    # forwarded headers, otherwise links, styles and scripts are generated as http:// and the
    # browser blocks them. Development only; production sets TRUSTED_PROXIES itself.
    if grep -q '^TRUSTED_PROXIES=$' .env; then
        sed -i 's#^TRUSTED_PROXIES=$#TRUSTED_PROXIES=*#' .env
    elif ! grep -q '^TRUSTED_PROXIES=' .env; then
        printf '\nTRUSTED_PROXIES=*\n' >> .env
    fi

    if [ -n "${CODESPACE_NAME:-}" ] && [ -n "${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-}" ]; then
        url="https://${CODESPACE_NAME}-8000.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"
        sed -i "s#^APP_URL=.*#APP_URL=${url}#" .env
    fi

    echo "[dev] npm install + build"
    if [ ! -d node_modules ]; then
        npm ci --no-audit --no-fund
    fi
    npm run build

    wait_for_db
    php artisan migrate --force
    php artisan app:ensure-bucket
    php artisan app:seed-if-empty
    php artisan storage:link >/dev/null 2>&1 || true
    php artisan optimize:clear >/dev/null
    touch storage/framework/.dev-ready
}

case "$role" in
    serve)
        rm -f storage/framework/.dev-ready
        prepare_app
        echo "[dev] CourtWatch is ready on http://localhost:8000 (admin: /admin)"
        exec php artisan serve --host=0.0.0.0 --port=8000
        ;;
    horizon)
        echo "[dev] waiting for the app container to finish preparing..."
        until [ -f storage/framework/.dev-ready ]; do sleep 5; done
        exec php artisan horizon
        ;;
    scheduler)
        until [ -f storage/framework/.dev-ready ]; do sleep 5; done
        exec php artisan schedule:work
        ;;
    *)
        exec "$@"
        ;;
esac
