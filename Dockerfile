# =========================================================================
# ASENA Enterprise - Production Dockerfile (PHP 8.2 FPM)
# =========================================================================
FROM php:8.2-fpm-alpine

# 1. Install System Dependencies & Build Tools
RUN apk add --no-cache \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    mysql-client \
    zip \
    unzip

# 2. Configure and Install Required PHP Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        gd \
        zip \
        intl \
        opcache \
        bcmath

# 3. Configure Production OPcache
RUN { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.revalidate_freq=2'; \
        echo 'opcache.fast_shutdown=1'; \
        echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# 4. Set Working Directory
WORKDIR /var/www/html

# 5. Copy Application Source Code
COPY . /var/www/html/

# 6. Ensure Storage & Upload Directories Have Proper Permissions
RUN mkdir -p /var/www/html/uploads/prescriptions \
    && mkdir -p /var/www/html/database/backups \
    && chown -R www-data:www-data /var/www/html/uploads /var/www/html/database/backups

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
