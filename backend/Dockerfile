FROM php:8.2-apache

# Install system dependencies + PHP extensions required by this project
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Install Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json composer.lock* ./

# Install PHP dependencies (production mode)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy backend application code
COPY . .

# Configure Apache document root to /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf

# Ensure logs dir exists and is writable
RUN mkdir -p /var/www/html/logs && chown -R www-data:www-data /var/www/html/logs

EXPOSE 80
CMD ["apache2-foreground"]
