# ---------- Runtime stage ----------
FROM php:8.4-fpm-alpine AS app

# System + build deps for PHP extensions
RUN apk add --no-cache \
  nginx \
  supervisor \
  bash \
  curl \
  icu-dev \
  oniguruma-dev \
  libzip-dev \
  postgresql-dev \
  $PHPIZE_DEPS \
  && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    intl \
    zip \
    opcache \
  && apk del --no-network $PHPIZE_DEPS \
  && rm -rf /var/cache/apk/*

WORKDIR /var/www/html

# Copy Laravel app + vendor from build stage
COPY --from=vendor /app /var/www/html

# Nginx + Supervisor config
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Laravel permissions
RUN mkdir -p storage bootstrap/cache \
  && chown -R www-data:www-data /var/www/html \
  && chmod -R 775 storage bootstrap/cache

COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080
CMD ["/start.sh"]
