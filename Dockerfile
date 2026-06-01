FROM ubuntu:noble

ARG VCS_REF
ARG VCS_URL
ARG BUILD_DATE
ARG PHP_VERSION=8.3

LABEL org.openaustralia.image.build.date=$BUILD_DATE \
      org.openaustralia.image.build.vcs-url=$VCS_URL \
      org.openaustralia.image.build.vcs-ref=$VCS_REF

# Install Apache and PHP
RUN apt-get update && \
    apt-get install -y apache2 libapache2-mod-php

# Install Perl modules needed by search/index.pl (Xapian indexing).
RUN apt-get update && \
    apt-get install -y perl libdbi-perl libdbd-mysql-perl libsearch-xapian-perl libhtml-parser-perl liberror-perl

# Install necessary php extensions
RUN apt-get install -y \
    autoconf \
    automake \
    build-essential \
    ca-certificates \
    libpq-dev \
    libxapian-dev \
    libtool \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-dev \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-pgsql \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-xdebug \
    php${PHP_VERSION}-zip \
    pkg-config \
    wget \
    xapian-tools \
    xz-utils

# Build PHP Xapian extension from source to match installed libxapian-dev.
RUN set -eux; \
    xapian_version="$(dpkg-query -W -f='${Version}' libxapian-dev | sed -E 's/^([0-9]+\.[0-9]+\.[0-9]+).*/\1/')"; \
    cd /tmp; \
    wget -O "xapian-bindings-${xapian_version}.tar.xz" "https://oligarchy.co.uk/xapian/${xapian_version}/xapian-bindings-${xapian_version}.tar.xz"; \
    tar -xf "xapian-bindings-${xapian_version}.tar.xz"; \
    cd "xapian-bindings-${xapian_version}"; \
    ./configure --with-php; \
    make -j"$(nproc)"; \
    make install; \
    printf 'extension=xapian.so\n' > /etc/php/${PHP_VERSION}/mods-available/xapian.ini; \
    phpenmod xapian; \
    rm -rf /tmp/xapian-bindings-* /tmp/*.tar.xz

# Enable php extensions.
RUN phpenmod pdo_mysql
RUN phpenmod xdebug

# Enable apache modules.
RUN a2enmod rewrite

RUN a2enmod php${PHP_VERSION}

# Create dirs for our app.
RUN mkdir -p /app/sharedbackup /app/shared/pwdata/members

# Set the working directory (default for apache images is /var/www/html)
WORKDIR /app

CMD ["apache2ctl", "-D", "FOREGROUND"]
