# Use the official Debian image as a base image
FROM debian:latest

# Install Nginx, PHP, PHP-FPM, and Nano
RUN apt-get update && \
    apt-get install -y nginx php8.2-fpm nano && \
    apt-get clean

# Ensure PHP-FPM is using the correct socket path
RUN sed -i 's|^listen = .*$|listen = /var/run/php-fpm.sock|' /etc/php/8.2/fpm/pool.d/www.conf

# Ensure PHP-FPM passes environment variables
RUN echo "env[API_KEY] = \$API_KEY" >> /etc/php/8.2/fpm/pool.d/www.conf

# Ensure the socket directory has the correct permissions
RUN mkdir -p /var/run/php && \
    chown -R www-data:www-data /var/run/php

# Remove the default Nginx configuration
RUN rm /etc/nginx/sites-enabled/default

# Copy custom Nginx configuration files from the host machine to the container
COPY nginx.conf /etc/nginx/sites-available/default
RUN ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/

# Copy the PHP file into the web directory
#COPY index.php /usr/share/nginx/html/
COPY remoteadmin.php /usr/share/nginx/html/
COPY instructions.txt /usr/share/nginx/
COPY instructions_no_api.txt /usr/share/nginx/

# Ensure PHP-FPM is running as a service
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Expose port 80 to the host
EXPOSE 80

# Start services
CMD ["/start.sh"]
