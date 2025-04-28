FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev

RUN docker-php-ext-install \
    pdo_mysql \
    zip \
    intl

# Configure PHP
RUN echo "memory_limit = 2G" > /usr/local/etc/php/conf.d/memory-limit.ini

WORKDIR /var/www

# Instalacja Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Install dependencies
RUN composer install --no-interaction --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/var 