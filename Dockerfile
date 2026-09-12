FROM php:8.2-fpm

# Instalar dependencias del sistema y extensiones necesarias
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip nginx supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

# Configurar límites de Nginx para permitir subidas de archivos grandes (Filament/Livewire)
RUN echo "client_max_body_size 100M;" >> /etc/nginx/nginx.conf

# Configurar permisos iniciales
RUN mkdir -p /app/storage/framework/{sessions,views,cache/data} \
    && mkdir -p /app/storage/app/public \
    && mkdir -p /app/storage/logs \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 80
CMD php artisan config:cache && php artisan route:cache && php artisan storage:link --force && php artisan serve --host=0.0.0.0 --port=80