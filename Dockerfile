FROM php:8.3-cli

ARG WWWGROUP=1000
ARG NODE_VERSION=20

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libpq-dev \
        postgresql-client \
        libzip-dev \
        libpng-dev \
        libicu-dev \
        libonig-dev \
        supervisor \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        zip \
        intl \
        bcmath \
        pcntl \
    && docker-php-ext-enable pcntl \
    && curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY docker/app/entrypoint.sh /usr/local/bin/app-entrypoint
COPY docker/app/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/app/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

RUN chmod +x /usr/local/bin/app-entrypoint

EXPOSE 8000 5173 8080
