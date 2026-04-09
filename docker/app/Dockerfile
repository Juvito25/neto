FROM php:8.3-fpm-alpine

# Dependencies
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    shadow \
    autoconf \
    gcc \
    g++ \
    make

# PHP Extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    gd

# Redis extension - use docker-php-ext-install instead of pecl
RUN pecl install redis-6.0.2 && docker-php-ext-enable redis

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Non-root user
RUN addgroup -g 1000 -S appgroup && \
    adduser -u 1000 -S appuser -G appgroup

WORKDIR /var/www

# Ensure cache and storage directories exist with correct permissions
RUN mkdir -p /var/www/bootstrap/cache /var/www/storage/framework/{cache,sessions,views} && \
    chown -R appuser:appgroup /var/www/bootstrap/cache /var/www/storage && \
    chmod -R 775 /var/www/bootstrap/cache /var/www/storage

RUN chown -R appuser:appgroup /var/www

USER appuser

EXPOSE 9000

CMD ["php-fpm"]
