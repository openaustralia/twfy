FROM ubuntu:noble

ARG VCS_REF
ARG VCS_URL
ARG BUILD_DATE

LABEL org.openaustralia.image.build.date=$BUILD_DATE \
      org.openaustralia.image.build.vcs-url=$VCS_URL \
      org.openaustralia.image.build.vcs-ref=$VCS_REF

# Install Apache and PHP
RUN apt-get update && \
    apt-get install -y apache2 libapache2-mod-php && \
    # Install necessary php extensions
    apt-get install -y libpq-dev php8.3-mysql php8.3-pgsql php8.3-xml php8.3-curl php8.3-mbstring php8.3-zip && \
    # Enable php extensions.
    phpenmod pdo_mysql && \
    # Enable apache modules.
    a2enmod rewrite php && \
    # Create dirs for our app.
    mkdir -p /app/sharedbackup /app/shared/pwdata/members

# Set the working directory (default for apache images is /var/www/html)
WORKDIR /app

CMD ["apache2ctl", "-D", "FOREGROUND"]
