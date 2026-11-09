# Dev-only image matching openaustralia production, as set up by the infrastructure repo's openaustralia role:
# Ubuntu 22.04 (jammy), PHP from ppa:ondrej/php, Xapian core 1.4.25 with PHP bindings built from the checksummed 1.4.22
# tarball, Node from NodeSource and Ubuntu's composer. Keep these in step with group_vars/openaustralia.yml.
FROM ubuntu:jammy

ARG PHP_VERSION=8.3
# Production's core library is 1.4.25 (xapian-config --version), while the bindings come from xapian_version's tarball
ARG XAPIAN_CORE_VERSION=1.4.25
ARG XAPIAN_VERSION=1.4.22
ARG XAPIAN_BINDINGS_SHA256=6b5454833ac52a3e32c0bb3a7290a5a2b50488d8918f2a45269557e4de9a31e5
ARG NODEJS_VERSION=22

ARG VCS_REF
ARG VCS_URL
ARG BUILD_DATE

LABEL org.openaustralia.image.build.date=$BUILD_DATE \
      org.openaustralia.image.build.vcs-url=$VCS_URL \
      org.openaustralia.image.build.vcs-ref=$VCS_REF

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && \
    apt-get install -y ca-certificates curl gnupg software-properties-common && \
    add-apt-repository -y ppa:ondrej/php && \
    mkdir -p /etc/apt/keyrings && \
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg && \
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_${NODEJS_VERSION}.x nodistro main" \
      > /etc/apt/sources.list.d/nodesource.list

# Apache and PHP, as production, plus pgsql and xdebug for development and tests
RUN apt-get update && \
    apt-get install -y \
    apache2 \
    libapache2-mod-php${PHP_VERSION} \
    php${PHP_VERSION} \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-dev \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-pgsql \
    php${PHP_VERSION}-xdebug \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-zip \
    composer \
    nodejs

# Ubuntu's composer pulls in the PPA's newest PHP too, so make PHP_VERSION the default, as production does
RUN update-alternatives --set php /usr/bin/php${PHP_VERSION} && \
    update-alternatives --set php-config /usr/bin/php-config${PHP_VERSION} && \
    update-alternatives --set phpize /usr/bin/phpize${PHP_VERSION}

# Perl modules needed by search/index.pl (Xapian indexing)
RUN apt-get install -y perl libdbi-perl libdbd-mysql-perl libsearch-xapian-perl libhtml-parser-perl liberror-perl

# Build tools for Xapian and its PHP bindings
RUN apt-get install -y \
    autoconf \
    automake \
    build-essential \
    libpq-dev \
    libtool \
    pkg-config \
    uuid-dev \
    wget \
    xz-utils \
    zlib1g-dev

# Production installed the Xapian core from ppa:xapian/backports, which now only carries a newer version for jammy, so
# build production's version from upstream's source. Its tarball has no recorded checksum; the bindings' one below does.
RUN set -eux; \
    cd /tmp; \
    wget -O "xapian-core-${XAPIAN_CORE_VERSION}.tar.xz" "https://oligarchy.co.uk/xapian/${XAPIAN_CORE_VERSION}/xapian-core-${XAPIAN_CORE_VERSION}.tar.xz"; \
    tar -xf "xapian-core-${XAPIAN_CORE_VERSION}.tar.xz"; \
    cd "xapian-core-${XAPIAN_CORE_VERSION}"; \
    ./configure --prefix=/usr/local; \
    make -j"$(nproc)"; \
    make install; \
    ldconfig; \
    rm -rf /tmp/xapian-core-*

RUN set -eux; \
    cd /tmp; \
    wget -O "xapian-bindings-${XAPIAN_VERSION}.tar.xz" "https://oligarchy.co.uk/xapian/${XAPIAN_VERSION}/xapian-bindings-${XAPIAN_VERSION}.tar.xz"; \
    echo "${XAPIAN_BINDINGS_SHA256}  xapian-bindings-${XAPIAN_VERSION}.tar.xz" | sha256sum -c -; \
    tar -xf "xapian-bindings-${XAPIAN_VERSION}.tar.xz"; \
    cd "xapian-bindings-${XAPIAN_VERSION}"; \
    ./configure --with-php PHP_CONFIG=/usr/bin/php-config${PHP_VERSION}; \
    make -j"$(nproc)"; \
    make install; \
    printf 'extension=xapian.so\n' > /etc/php/${PHP_VERSION}/mods-available/xapian.ini; \
    phpenmod xapian; \
    rm -rf /tmp/xapian-bindings-*

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
