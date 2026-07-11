# ─── Stage 1: Dependencies ───────────────────────────────────────────
FROM php:8.2-cli AS dependencies

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    unzip \
    curl \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    xml \
    bcmath \
    gd \
    zip

# Node.js 20
COPY --from=node:20-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --optimize-autoloader --prefer-dist

# Install Node dependencies and build
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund && npm cache clean --force

# ─── Stage 2: Application ───────────────────────────────────────────
FROM php:8.2-fpm AS app

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    xml \
    bcmath \
    gd \
    zip \
    opcache

# PHP production config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# OPcache config
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=60'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /app

# Copy from dependencies stage
COPY --from=dependencies /app/vendor /app/vendor
COPY --from=dependencies /app/node_modules /app/node_modules

# Copy application files
COPY . .

# Build frontend assets
RUN npm run build && rm -rf node_modules

# Storage permissions
RUN mkdir -p storage/framework/{cache,sessions,testing,views} \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER www-data

EXPOSE 8000

# Render passes a dynamic PORT env var — use shell form to expand it
# Falls back to 8000 if PORT is not set (local dev compatibility)
CMD ["sh", "-c", "exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]

# ─── Stage 3: Queue Worker ──────────────────────────────────────────
FROM app AS queue-worker

CMD ["php", "artisan", "queue:work", "--tries=3", "--timeout=60"]

# ─── Stage 4: Scheduler ────────────────────────────────────────────
FROM app AS scheduler

CMD ["php", "artisan", "schedule:work"]
