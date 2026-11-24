FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates libcurl4-openssl-dev libonig-dev \
    && docker-php-ext-install -j"$(nproc)" curl mbstring pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

RUN printf '<Directory /var/www/html>\n    AllowOverride All\n</Directory>\n' \
    > /etc/apache2/conf-available/musictrack.conf \
    && a2enconf musictrack

COPY . /var/www/html/
COPY includes/config.example.php /var/www/html/includes/config.php

EXPOSE 80
