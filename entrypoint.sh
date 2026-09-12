#!/bin/sh
composer dump-autoload --optimize --no-dev --no-interaction
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan storage:link --force
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf