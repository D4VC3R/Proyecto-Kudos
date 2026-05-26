# Usamos Debian 12 Slim. Motor rápido (glibc) pero peso pluma.
FROM php:8.4-cli-bookworm

LABEL maintainer="Tu Nombre/Proyecto"

ARG WWWGROUP=1000
ARG WWWUSER=1000

WORKDIR /var/www/html

ENV SUPERVISOR_PHP_COMMAND="php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_USER="sail"

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    ca-certificates \
    postgresql-client \
    libpq-dev \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    supervisor \
    libcap2-bin \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        bcmath \
        zip \
        pcntl \
        intl \
        opcache \
        exif

RUN pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN groupadd --force -g $WWWGROUP sail \
    && useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u $WWWUSER sail \
    && setcap "cap_net_bind_service=+ep" /usr/local/bin/php

RUN mkdir -p /var/log/supervisor && chown sail:sail /var/log/supervisor

COPY php.ini /usr/local/etc/php/conf.d/99-sail.ini
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80/tcp

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]