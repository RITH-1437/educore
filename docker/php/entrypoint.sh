#!/usr/bin/env bash
set -euo pipefail

# Ensure Laravel runtime directories exist and are writable.
mkdir -p storage/framework/{sessions,views,cache/data} bootstrap/cache
chmod -R ug+rw storage bootstrap/cache 2>/dev/null || true

# Install Composer dependencies if they are not present yet.
if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --no-progress
fi

# Reference .env file for Laravel.
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
    fi
fi

# Generate a persistent application key if one is not set.
if [ -f .env ] && ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction
fi

# Link storage so /storage/ URLs work.
# Only when missing: backend, queue and scheduler start together on the same
# bind mount, and `--force` from all three raced ("symlink(): File exists").
if [ ! -L public/storage ]; then
    php artisan storage:link 2>/dev/null || true
fi

# Warm the application cache only in production. In development a cached
# config hides .env/phpunit.xml changes — the test suite would then ignore
# DB_DATABASE=educore_test and run against the development database — and
# cached routes hide new routes. Package discovery is refreshed either way.
if [ "${APP_ENV:-}" = "production" ]; then
    php artisan optimize 2>/dev/null || true
else
    php artisan optimize:clear 2>/dev/null || true
    php artisan package:discover --ansi 2>/dev/null || true
fi

exec "$@"