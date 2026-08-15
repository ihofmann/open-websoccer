FROM php:8.5-apache

LABEL org.opencontainers.image.title="OpenWebSoccer-Sim"
LABEL org.opencontainers.image.description="Slim Apache + PHP 8.5 image for OpenWebSoccer-Sim"
LABEL org.opencontainers.image.source="https://github.com/ihofmann/open-websoccer"

# System libraries required by the GD extension (JPEG, PNG, FreeType)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions that are NOT already compiled into
# the php:8.5-apache base image.  In PHP 8.5 the following are built in by
# default and therefore must NOT be passed to docker-php-ext-install:
#   dom, xml, simplexml, curl, mbstring, lexbor
# Only these two need to be added:
#   mysqli - database access (DbConnection)
#   gd     - image upload / resizing (profile & club pictures)
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" mysqli gd

# Enable Apache modules:
#   rewrite        - clean URLs / future routing
#   access_compat  - backward compatible Order/Deny/Allow (.htaccess in webservices/micropayment)
RUN a2enmod rewrite access_compat

# The application lives in the Apache document root
COPY websoccer/ /var/www/html/

# Folders that must be writable by the web server at runtime
RUN mkdir -p /var/www/html/generated \
             /var/www/html/cache/templates \
             /var/www/html/uploads/club \
             /var/www/html/uploads/cup \
             /var/www/html/uploads/player \
             /var/www/html/uploads/sponsor \
             /var/www/html/uploads/stadium \
             /var/www/html/uploads/stadiumbuilder \
             /var/www/html/uploads/stadiumbuilding \
             /var/www/html/uploads/users \
    && chown -R www-data:www-data /var/www/html/generated /var/www/html/cache /var/www/html/uploads \
    && chown www-data:www-data /var/www/html/admin/config/jobs.xml /var/www/html/admin/config/termsandconditions.xml \
    && chmod -R 775 /var/www/html/generated /var/www/html/cache /var/www/html/uploads /var/www/html/admin/config/jobs.xml /var/www/html/admin/config/termsandconditions.xml

EXPOSE 80
