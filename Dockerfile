FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev zip unzip nginx supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

RUN echo "client_max_body_size 100M;" >> /etc/nginx/nginx.conf

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

RUN mkdir -p /app/storage/framework/{sessions,views,cache/data} \
    && mkdir -p /app/storage/app/public \
    && mkdir -p /app/storage/logs \
    && chmod -R 777 storage bootstrap/cache

COPY entrypoint.sh /usr/bin/entrypoint.sh
RUN chmod +x /usr/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]