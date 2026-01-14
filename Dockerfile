# ---------- Build stage (composer deps) ----------
FROM composer:2 AS vendor
WORKDIR /app

# Copy composer files FIRST for caching
COPY composer.json composer.lock ./

# Copy the rest of the app so artisan exists for composer scripts
COPY . .

# Install dependencies (artisan is available now)
RUN composer install \
  --no-dev \
  --prefer-dist \
  --no-interaction \
  --no-progress \
  --optimize-autoloader


# ---------- Runtime stage ----------
FROM php:8.3-fpm-alpine AS app

# System deps
RUN apk add --no-cache \
  nginx \
  supervisor \
  bash \
  icu-dev \
  oniguruma-dev \
  libzip-dev \
  postgresql-dev \
  curl \
  git \
  && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    intl \
    zip \
    opcache \
  && rm -rf /var/cache/apk/*

WORKDIR /var/www/html

# Copy Laravel app + vendor from build stage
COPY --from=vendor /app /var/www/html

# Nginx + Supervisor config
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Laravel permissions (Render runs as root by default, but keep it correct)
RUN mkdir -p storage bootstrap/cache \
  && chown -R www-data:www-data /var/www/html \
  && chmod -R 775 storage bootstrap/cache

# Render provides $PORT. We'll inject it into nginx conf at runtime.
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
