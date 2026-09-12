#!/bin/sh
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:cache
php artisan storage:link --force
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf