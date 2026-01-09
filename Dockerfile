# Use the specified base image
FROM php:7.4.33-apache-bullseye

# Install necessary system libraries and PHP extensions (example: pdo_mysql)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql mysqli

RUN apt-get update && apt-get install -y vim

# Copy custom configuration files if needed
# COPY ./inc/php/php.ini $PHP_INI_DIR/php.ini
COPY conf/httpd-docker.conf /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite
RUN service apache2 restart


# Set the working directory (default for apache images is /var/www/html)
WORKDIR /app/www