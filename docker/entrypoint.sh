#!/bin/sh
set -euo pipefail

cd /var/www/html

echo "[entrypoint] Installing dependencies (composer install)"
composer install --no-interaction --prefer-dist --optimize-autoloader

if [ -z "${APP_KEY:-}" ] || [ "$APP_KEY" = "" ]; then
  echo "[entrypoint] Generating APP_KEY"
  php artisan key:generate -n || true
fi

if [ -z "${JWT_SECRET:-}" ] || [ "$JWT_SECRET" = "" ]; then
  echo "[entrypoint] Generating JWT_SECRET"
  php artisan jwt:secret -n || true
fi

echo "[entrypoint] Running migrations (with seed)"
attempt=0
until php artisan migrate --seed --no-interaction; do
  attempt=$((attempt+1))
  if [ "$attempt" -ge 10 ]; then
    echo "[entrypoint] Migration retries exceeded" >&2
    exit 1
  fi
  echo "[entrypoint] Migration failed, retrying in 3s (attempt $attempt)"
  sleep 3
done

if [ "${START_QUEUE_WORKER:-1}" = "1" ]; then
  echo "[entrypoint] Starting queue worker in background"
  php artisan queue:work --queue=default --sleep=1 --tries=3 &
fi

echo "[entrypoint] Starting PHP dev server on 0.0.0.0:9000"
exec php -S 0.0.0.0:9000 -t public

