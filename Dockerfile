# FROM php:7.4.33-apache-bullseye
FROM ubuntu:xenial

# Install Apache and PHP
RUN apt-get update && apt-get install -y apache2 php7.0 libapache2-mod-php7.0

# # Install necessary php extensions
RUN apt-get install -y libpq-dev php7.0-mysql php7.0-pgsql php7.0-xml php7.0-curl php7.0-mbstring php7.0-zip
RUN phpenmod pdo_mysql
# COPY scripts/docker-php-ext-install /usr/local/bin/docker-php-ext-install
# COPY scripts/docker-php-source /usr/local/bin/docker-php-source
# RUN chmod +x /usr/local/bin/docker-php-ext-install /usr/local/bin/docker-php-source
# RUN docker-php-ext-install pdo_mysql mysqli

RUN a2enmod rewrite
RUN service apache2 restart


WORKDIR /app/www

# Set the working directory (default for apache images is /var/www/html)
RUN mkdir /app/shared
RUN mkdir -p /app/shared/pwdata/members
RUN mkdir -p /app/shared/backup
WORKDIR /app
CMD ["apache2ctl", "-D", "FOREGROUND"]
