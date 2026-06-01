FROM dunglas/frankenphp:1.4-php8.4-bookworm

ENV FRANKENPHP_CONFIG="web-service" \
    APP_ENV=production \
    APP_DEBUG=false

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    git \
    && curl -sS https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o /usr/local/bin/install-php-extensions \
    && chmod +x /usr/local/bin/install-php-extensions

RUN install-php-extensions \
    pcntl \
    bcmath \
    pdo_pgsql \
    pgsql \
    redis \
    gd \
    intl \
    zip \
    opcache \
    exif

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app

RUN composer install --no-dev --no-scripts --optimize-autoloader

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

EXPOSE 8080
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
