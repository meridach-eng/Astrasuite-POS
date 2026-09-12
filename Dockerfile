FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev zip unzip nginx supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Configuración de Nginx para Laravel y límites de subida
RUN echo "client_max_body_size 100M;" >> /etc/nginx/nginx.conf

# Crear configuración básica de Nginx para el proyecto Laravel
RUN echo 'server { \
    listen 80; \
    index index.php index.html; \
    root /app/public; \
    location / { \
        try_files \$uri \$uri/ /index.php?\$query_string; \
    } \
    location ~ \.php\$ { \
        include fastcgi_params; \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name; \
    } \
}' > /etc/nginx/sites-available/default

RUN mkdir -p /app/storage/framework/{sessions,views,cache/data} \
    && mkdir -p /app/storage/app/public \
    && mkdir -p /app/storage/logs \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 80

# Script de arranque para PHP-FPM y Nginx en segundo plano
CMD php artisan config:cache && php artisan route:cache && php artisan storage:link --force && service php8.4-fpm start && nginx -g "daemon off;"