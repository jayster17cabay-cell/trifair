# v7 - cache dependency installs so code-only deploys never re-download packages
FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev libzip-dev libsqlite3-dev libpq-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip fileinfo opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Pin Composer instead of tracking :latest, so a new Composer release can never
# silently break a Render deploy again.
COPY --from=composer:2.11.0 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    COMPOSER_MAX_PARALLEL_HTTP=6

WORKDIR /var/www/html

# 1. Dependency layer. Only the Composer manifest is copied first, so this
#    layer stays cached for every deploy that does not touch composer.json
#    or composer.lock. That keeps the build fast and avoids re-downloading
#    ~70 packages from the GitHub API on every push (a source of flaky,
#    rate-limited builds).
COPY composer.json composer.lock ./

# Retry transient package-registry/network failures instead of failing the
# whole deploy on a single hiccup.
RUN set -eux; \
    for attempt in 1 2 3; do \
        if composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist --no-progress; then \
            break; \
        fi; \
        if [ "$attempt" = "3" ]; then \
            echo "composer install failed after 3 attempts"; \
            exit 1; \
        fi; \
        echo "composer install failed (attempt $attempt), retrying in 5s..."; \
        sleep 5; \
    done

# 2. Application source. Changing app code no longer invalidates the layer above.
COPY . .

RUN cp .env.example .env

RUN mkdir -p database storage/framework/sessions storage/framework/views storage/framework/cache storage/logs \
    && touch database/database.sqlite storage/logs/laravel.log

# Build the optimized autoloader now that app/ and database/ are present.
RUN composer dump-autoload --no-dev --optimize --no-scripts --no-interaction

RUN php artisan key:generate --force \
    && php artisan package:discover --ansi

RUN echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=25M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_file_uploads=10" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini

# OPcache in production mode: opcode cache + cached config/views make every
# request skip re-parsing PHP and re-reading dotenv (big win on cold requests).
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.enable_cli=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=192" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=30000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

RUN php artisan storage:link || true

RUN chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["/bin/bash", "-c", "mkdir -p /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views /var/www/html/storage/framework/cache /var/www/html/storage/app/public /var/www/html/storage/logs && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache && php artisan storage:link 2>/dev/null; php artisan optimize:clear; php artisan config:cache || php artisan config:clear; php artisan view:cache || php artisan view:clear; php artisan migrate --force; exec apache2-foreground"]
