# =============================================================================
# QHSSE App — Dockerfile
# PHP 8.3-FPM + Composer + Node 22 (untuk npm run build saat deploy)
# =============================================================================

# ── Stage 1: Node build ───────────────────────────────────────────────────────
FROM node:22-alpine AS node-builder

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --prefer-offline

COPY resources/ resources/
COPY vite.config.js tailwind.config.js postcss.config.js tsconfig.json ./
COPY public/ public/

RUN npm run build

# ── Stage 2: PHP app ──────────────────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS app

# System deps
RUN apk add --no-cache \
    bash curl git unzip shadow \
    libpng-dev libjpeg-turbo-dev libwebp-dev \
    libzip-dev libxml2-dev oniguruma-dev \
    postgresql-dev icu-dev \
    linux-headers $PHPIZE_DEPS

# PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) \
        pdo pdo_pgsql pgsql \
        pdo_mysql \
        gd zip xml mbstring bcmath opcache \
        intl pcntl

# Redis extension via PECL
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Non-root user (sama dengan www-data di Alpine)
ARG UID=1000
RUN usermod -u ${UID} www-data 2>/dev/null || true

WORKDIR /var/www/html

# Install PHP dependencies (production)
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts

# Copy seluruh source
COPY --chown=www-data:www-data . .

# Copy Vite build hasil stage 1
COPY --from=node-builder --chown=www-data:www-data /app/public/build/ public/build/

# Storage + cache dirs
RUN mkdir -p storage/framework/{sessions,views,cache/data} \
             storage/app/{private,public} \
             storage/logs \
             bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# php.ini production overrides (dipasang dari docker/php/)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-qhsse.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data
EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
