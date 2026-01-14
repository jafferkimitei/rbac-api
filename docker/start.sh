#!/usr/bin/env bash
set -e

# Render sets PORT; default for local
PORT="${PORT:-8080}"

# Replace nginx listen port dynamically
sed -i "s/listen 8080;/listen ${PORT};/g" /etc/nginx/nginx.conf

# Laravel optimizations (safe-ish for container startup)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Optional: run migrations automatically (recommended only if you’re ok with it)
# php artisan migrate --force || true

# Start php-fpm (background) + nginx (foreground via supervisor)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
