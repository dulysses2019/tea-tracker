# Use an official PHP image with Apache
FROM php:8.1-apache

# Install necessary PHP extensions for MySQL and the mysql-client for the entrypoint script
RUN apt-get update && apt-get install -y default-mysql-client && \
    docker-php-ext-install pdo pdo_mysql

# Copy the application source code
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type d -exec chmod 775 {} \; && \
    find /var/www/html -type f -exec chmod 664 {} \;

# Copy the entrypoint script and make it executable
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Set the entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]

# The default command to run after the entrypoint (starts Apache)
CMD ["apache2-foreground"]

# Expose port 80
EXPOSE 80