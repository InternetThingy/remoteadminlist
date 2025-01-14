# Use the official Debian image as a base image
FROM debian:latest

# Install Nginx, PHP, PHP-FPM, and Nano
RUN apt-get update && \
    apt-get install -y nginx php8.2-fpm nano net-tools && \
    apt-get clean

# Ensure PHP-FPM is using TCP on port 9009
RUN sed -i 's|^listen = .*$|listen = 127.0.0.1:9009|' /etc/php/8.2/fpm/pool.d/www.conf && \
    sed -i 's|;listen.owner = www-data|listen.owner = www-data|' /etc/php/8.2/fpm/pool.d/www.conf && \
    sed -i 's|;listen.group = www-data|listen.group = www-data|' /etc/php/8.2/fpm/pool.d/www.conf && \
    sed -i 's|;listen.mode = 0660|listen.mode = 0660|' /etc/php/8.2/fpm/pool.d/www.conf && \
    sed -i '/^user = www-data/d' /etc/php/8.2/fpm/pool.d/www.conf && \
    sed -i '/^group = www-data/d' /etc/php/8.2/fpm/pool.d/www.conf && \
    echo 'env[API_KEY] = $API_KEY' >> /etc/php/8.2/fpm/pool.d/www.conf

# Ensure required directories exist and are owned by www-data
RUN mkdir -p /var/log/nginx /var/lib/nginx /run/nginx && \
    touch /var/log/php8.2-fpm.log && \
    chown -R www-data:www-data /var/log/php8.2-fpm.log /var/log/nginx /var/lib/nginx /run/nginx && \
    chmod -R 775 /var/lib/nginx /run/nginx

# Update the global Nginx configuration file
RUN sed -i '/^user /d' /etc/nginx/nginx.conf && \
    sed -i '/^pid /d' /etc/nginx/nginx.conf && \
    sed -i '1i pid /run/nginx/nginx.pid;' /etc/nginx/nginx.conf

# Copy the server-specific configuration file
COPY nginx.conf /etc/nginx/sites-available/default
RUN [ ! -e /etc/nginx/sites-enabled/default ] || rm /etc/nginx/sites-enabled/default && \
    ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/

# Copy the PHP file and other required files into the web directory
COPY remoteadmin.php /usr/share/nginx/html/
COPY instructions.txt /usr/share/nginx/
COPY instructions_no_api.txt /usr/share/nginx/

# Ensure PHP-FPM is running as a service
COPY start.sh /start.sh
RUN chmod +x /start.sh && \
    chown www-data:www-data /start.sh

# Expose port 80 to the host
EXPOSE 80

# Start services as www-data
USER www-data
CMD ["/start.sh"]
