FROM php:8.4-apache

# Install system packages and PHP extensions required by Laravel.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        nodejs \
        npm \
        libzip-dev \
        libicu-dev \
        libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql zip intl curl pcntl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/appsignals/backend/public
WORKDIR /var/www/appsignals/backend

COPY backend/ /var/www/appsignals/backend

RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && npm ci \
    && npm run build \
    && chown -R www-data:www-data /var/www/appsignals/backend/storage /var/www/appsignals/backend/bootstrap/cache

RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
