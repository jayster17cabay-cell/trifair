FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev libzip-dev libsqlite3-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN cp .env.example .env

RUN mkdir -p database storage/framework/sessions storage/framework/views storage/framework/cache storage/logs \
    && touch database/database.sqlite storage/logs/laravel.log

RUN composer install --no-dev --optimize-autoloader --no-scripts

RUN php artisan key:generate --force \
    && php artisan config:cache \
    && php artisan package:discover --ansi

RUN chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["/bin/bash", "-c", "php artisan migrate --force && php artisan db:seed --force; exec apache2-foreground"]
