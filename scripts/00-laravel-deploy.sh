#!/bin/sh
set -e

echo "==> [Deploy] Linking storage..."
php artisan storage:link --force || true

echo "==> [Deploy] Running migrations..."
php artisan migrate --force

echo "==> [Deploy] Seeding default accounts (idempotent)..."
php artisan db:seed --force

echo "==> [Deploy] Caching config, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> [Deploy] Application ready to serve!"
