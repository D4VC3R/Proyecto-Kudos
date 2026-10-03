# syntax=docker/dockerfile:1
#
# Imagen de PRODUCCIÓN de la API de Kudos (el desarrollo local sigue usando Sail: compose.yml + docker/8.5).
# Construcción multietapa; compose.prod.yml usa dos de sus etapas:
#   - target "app": PHP-FPM con el código y las dependencias (servicios app y worker).
#   - target "web": Nginx interno que sirve public/ y pasa las peticiones PHP a app:9000.
# Las versiones están fijadas: una imagen "latest" puede cambiar entre dos despliegues sin que cambie el código.

ARG PHP_VERSION=8.4
ARG ALPINE_VERSION=3.24
ARG NGINX_VERSION=1.30
ARG COMPOSER_VERSION=2.10
ARG PHPREDIS_VERSION=6.3.0

FROM composer:${COMPOSER_VERSION} AS composer

# --- base: PHP-FPM con las extensiones que necesita el proyecto ----------------------------------
FROM php:${PHP_VERSION}-fpm-alpine${ALPINE_VERSION} AS base

ARG PHPREDIS_VERSION

# Las librerías de ejecución se quedan; las de compilación (.build-deps) se borran en la misma capa
# para que no ocupen espacio en la imagen final.
RUN set -eux; \
    apk add --no-cache freetype icu-libs libjpeg-turbo libpng libpq libwebp libzip; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS freetype-dev icu-dev libjpeg-turbo-dev \
        libpng-dev libpq-dev libwebp-dev libzip-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j"$(nproc)" bcmath exif gd intl opcache pcntl pdo_pgsql zip; \
    pecl install "redis-${PHPREDIS_VERSION}"; \
    docker-php-ext-enable redis; \
    apk del .build-deps; \
    rm -rf /tmp/pear

# --- vendor: dependencias de Composer, sin las de desarrollo -------------------------------------
FROM base AS vendor

COPY --from=composer /usr/bin/composer /usr/bin/composer
WORKDIR /app

# Primero solo composer.json/lock: mientras no cambien, Docker reutiliza esta capa y no reinstala nada.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
RUN set -eux; \
    composer dump-autoload --no-dev --optimize --no-scripts --no-interaction; \
    # Permisos deterministas (en el repo hay ficheros 755/777): directorios 755 y ficheros 644.
    find . -type d -exec chmod 755 {} +; \
    find . -type f -exec chmod 644 {} +

# --- app: imagen final de PHP-FPM ----------------------------------------------------------------
FROM base AS app

WORKDIR /var/www/html

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/prod/php.ini "$PHP_INI_DIR/conf.d/zz-kudos.ini"
COPY docker/prod/php-fpm.conf /usr/local/etc/php-fpm.d/zz-kudos.conf
COPY --chmod=755 docker/prod/entrypoint.sh /usr/local/bin/kudos-entrypoint

# El código pertenece a root: PHP (www-data) puede leerlo pero no modificarlo.
COPY --from=vendor /app /var/www/html

# Solo storage/ y bootstrap/cache/ son escribibles por www-data. El enlace public/storage es absoluto
# para que funcione igual en el contenedor de Nginx, que monta el mismo volumen en la misma ruta.
RUN set -eux; \
    php artisan package:discover --ansi; \
    mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs/moderation bootstrap/cache; \
    ln -sfn /var/www/html/storage/app/public public/storage; \
    chown -R www-data:www-data storage bootstrap/cache

USER www-data

ENTRYPOINT ["kudos-entrypoint"]
CMD ["php-fpm"]

# --- web: Nginx interno --------------------------------------------------------------------------
FROM nginx:${NGINX_VERSION}-alpine AS web

COPY docker/prod/nginx.conf /etc/nginx/conf.d/default.conf
# Solo public/ (index.php, robots.txt, favicon y el enlace a storage). El PHP se ejecuta en "app".
COPY --from=app /var/www/html/public /var/www/html/public
