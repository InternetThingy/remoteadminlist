# Use a specific Debian image tag to avoid uncontrolled updates
FROM debian:latest

# Set a non-root user for the container
RUN useradd -m -s /bin/bash webral

# Install Nginx, PHP, PHP-FPM, and Nano with minimal packages and cleaning up afterwards
RUN apt-get update && \
    apt-get install -y --no-install-recommends nginx php8.2-fpm nano && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

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

# Copy the PHP and supporting files into the container
COPY remoteadmin.php /usr/share/nginx/html/
COPY instructions.txt /usr/share/nginx/
COPY instructions_no_api.txt /usr/share/nginx/

# Ensure PHP-FPM is running as a service
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Change ownership of application files to the non-root user
RUN chown -R webral:webral /usr/share/nginx/html /usr/share/nginx

# Switch to non-root user
USER webral

# Expose port 80 to the host
EXPOSE 80

# Add a HEALTHCHECK to verify the Nginx service is running
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 CMD curl --fail http://localhost || exit 1

# Start services
CMD ["/start.sh"]
