FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers ssl socache_shmcb
RUN sed -i 's/Listen 80//' /etc/apache2/ports.conf

RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/apache-selfsigned.key \
    -out /etc/ssl/certs/apache-selfsigned.crt \
    -subj "/C=IN/ST=Telangana/L=Hyderabad/O=Personal/OU=Dev/CN=localhost"

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

RUN mkdir -p /var/uploads && chown www-data:www-data /var/uploads && chmod 750 /var/uploads

COPY app/ /var/www/html/
COPY docker/create_accounts.php /var/www/create_accounts.php
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 443
ENTRYPOINT ["entrypoint.sh"]