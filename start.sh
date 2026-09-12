#!/bin/sh
set -e

echo "⏳ Esperando a que MySQL esté listo..."

until nc -z "$DB_HOST" "$DB_PORT"; do
  echo "MySQL no está listo en $DB_HOST:$DB_PORT — reintentando..."
  sleep 2
done

echo "✅ MySQL está listo. Iniciando Laravel..."

chmod -R 775 storage bootstrap/cache || true
php artisan storage:link || true
php artisan migrate --force || true

php-fpm -D
nginx -c /app/nginx.conf -g "daemon off;"
