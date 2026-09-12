FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev zip unzip nginx supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

RUN echo "client_max_body_size 100M;" >> /etc/nginx/nginx.conf

RUN mkdir -p /app/storage/framework/{sessions,views,cache/data} \
    && mkdir -p /app/storage/app/public \
    && mkdir -p /app/storage/logs \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 80
CMD ["sh", "-c", "php artisan config:cache && php artisan route:cache && php artisan storage:link --force && php artisan serve --host=0.0.0.0 --port=80"]