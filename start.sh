#!/bin/sh

# Start PHP-FPM
php-fpm8.2 &

# Ensure the socket directory has the correct permissions
chown -R www-data:www-data /var/run/php

# Start Nginx
nginx -g "daemon off;"
