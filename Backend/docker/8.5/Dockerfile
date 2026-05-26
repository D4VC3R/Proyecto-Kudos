FROM php:8.4-cli-alpine

LABEL maintainer="Tu Nombre/Proyecto"

ARG WWWGROUP=1000
ARG WWWUSER=1000

WORKDIR /var/www/html

ENV SUPERVISOR_PHP_COMMAND="php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_USER="sail"

RUN apk add --no-cache \
    curl \
    ca-certificates \
    postgresql-client \
    postgresql-dev \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    supervisor \
    linux-headers \
    libcap \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
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

RUN mkdir -p /usr/src/php/ext/redis \
    && curl -fsSL https://github.com/phpredis/phpredis/archive/refs/tags/6.1.0.tar.gz | tar xvz -C /usr/src/php/ext/redis --strip 1 \
    && docker-php-ext-install redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN addgroup -g $WWWGROUP sail \
    && adduser -D -u $WWWUSER -G sail -s /bin/sh sail \
    && setcap "cap_net_bind_service=+ep" /usr/local/bin/php

RUN mkdir -p /var/log/supervisor && chown sail:sail /var/log/supervisor

COPY php.ini /usr/local/etc/php/conf.d/99-sail.ini
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80/tcp

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
