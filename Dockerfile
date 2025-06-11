# Use official PHP 8.3 image with Apache
FROM php:8.3-apache

# Set working directory
WORKDIR /app

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql zip mbstring gd xml

# Enable Apache Rewrite Module (important for Yii2 routing)
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy custom Apache vhost config
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf
