ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
        libwebp-dev libicu-dev libxml2-dev libxslt1-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" bcmath ftp gd intl pdo_mysql soap sockets xsl zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN printf 'memory_limit=4G\nmax_execution_time=0\n' > /usr/local/etc/php/conf.d/magento.ini

ENV COMPOSER_HOME=/composer COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /magento
