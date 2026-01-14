# ---------- Build stage (composer deps) ----------
FROM composer:2 AS vendor_deps
WORKDIR /app

COPY composer.json composer.lock ./
COPY . .

RUN test -f artisan

RUN composer install \
  --no-dev \
  --prefer-dist \
  --no-interaction \
  --no-progress \
  --optimize-autoloader

# ---------- Runtime stage ----------
FROM php:8.4-fpm-alpine AS app

RUN apk add --no-cache \
  nginx \
  supervisor \
  bash \
  icu-dev \
  oniguruma-dev \
  libzip-dev \
  postgresql-dev \
  curl \
  && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    intl \
    zip \
    opcache \
  && rm -rf /var/cache/apk/*

WORKDIR /var/www/html

COPY --from=vendor_deps /app /var/www/html

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN mkdir -p storage bootstrap/cache \
  && chown -R www-data:www-data /var/www/html \
  && chmod -R 775 storage bootstrap/cache

COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080
CMD ["/start.sh"]
