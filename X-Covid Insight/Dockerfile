# Use PHP with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y libssl-dev pkg-config

# Install and enable MongoDB extension
RUN pecl install mongodb && echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini

# Enable Apache rewrite module (if needed)
RUN a2enmod rewrite

# Copy website files to Apache document root
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Expose port 80
EXPOSE 80

# Enable output buffering in PHP
RUN echo "output_buffering=On" >> /usr/local/etc/php/conf.d/custom.ini

# Start Apache
CMD ["apache2-foreground"]
