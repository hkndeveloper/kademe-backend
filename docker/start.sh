#!/bin/sh

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Start Nginx & PHP-FPM
php-fpm -D
nginx -g "daemon off;"
