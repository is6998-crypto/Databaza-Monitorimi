#!/usr/bin/env sh
# CourtWatch production entrypoint. Roles: web (default), horizon, scheduler, or any command.
set -eu
cd /app

role="${1:-web}"

require() {
    eval "value=\${$1:-}"
    if [ -z "$value" ]; then
        echo "[courtwatch] $1 is not set: see .env.production.example and docs/DEPLOY.md" >&2
        exit 1
    fi
}

warm_caches() {
    # Fresh copy of the committed API docs into the storage volume.
    mkdir -p storage/app/private/scribe storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
    cp -f /opt/courtwatch/scribe/* storage/app/private/scribe/
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
}

case "$role" in
    web)
        require APP_KEY
        require BLIND_INDEX_KEY
        warm_caches
        if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
            php artisan migrate --force
            php artisan app:seed-if-empty
        fi
        exec frankenphp run --config /etc/frankenphp/Caddyfile
        ;;
    horizon)
        warm_caches
        exec php artisan horizon
        ;;
    scheduler)
        warm_caches
        exec php artisan schedule:work
        ;;
    *)
        exec "$@"
        ;;
esac
