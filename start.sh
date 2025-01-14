#!/bin/sh

# Start PHP-FPM
php-fpm8.2 &

# Start Nginx
nginx -g "daemon off;"
