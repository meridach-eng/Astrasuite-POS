FROM php:8.4-fpm

# Instalar dependencias del sistema y extensiones necesarias para Filament/Laravel
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev zip unzip nginx supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Instalar Composer oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Instalar dependencias ignorando restricciones de plataforma para PHP 8.4
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Configurar el tamaño máximo de subida en Nginx (Evita el fallo de subidas en Livewire)
RUN echo "client_max_body_size 100M;" >> /etc/nginx/nginx.conf

# Configurar el servidor virtual de Nginx para Laravel
RUN echo 'server { \
    listen 80; \
    index index.php index.html; \
    root /app/public; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~ \.php$ { \
        include fastcgi_params; \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
    } \
}' > /etc/nginx/sites-available/default

# Configurar Supervisor para mantener PHP-FPM y Nginx corriendo en paralelo
RUN echo '[supervisord] \
nodaemon=true \
\
[program:php-fpm] \
command=php-fpm \
autostart=true \
autorestart=true \
\
[program:nginx] \
command=nginx -g "daemon off;" \
autostart=true \
autorestart=true' > /etc/supervisor/conf.d/supervisord.conf

# Crear y dar permisos totales a las carpetas de almacenamiento
RUN mkdir -p /app/storage/framework/{sessions,views,cache/data} \
    && mkdir -p /app/storage/app/public \
    && mkdir -p /app/storage/logs \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 80

# Comando de arranque que limpia cachés residuales antes de iniciar los servicios
CMD php artisan optimize:clear && php artisan config:clear && php artisan cache:clear && php artisan route:cache && php artisan storage:link --force && supervisord -c /etc/supervisor/conf.d/supervisord.conf