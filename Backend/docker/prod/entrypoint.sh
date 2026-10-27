#!/bin/sh
# Punto de entrada de los contenedores app, worker y scheduler (y de "docker compose run app ...").
set -e

# Cachea configuración, rutas y eventos con las variables de entorno de ESTE contenedor.
# Se hace al arrancar y no al construir la imagen porque la imagen no contiene el .env: las variables
# llegan con env_file en tiempo de ejecución. Cada contenedor tiene su propio bootstrap/cache,
# así que app, worker y scheduler generan cada uno la suya.
# No se usa "artisan optimize" porque incluye view:cache, que falla al no existir resources/views
# (es una API: las únicas vistas son las de los correos del framework, que se compilan al usarse).
php artisan config:cache
php artisan route:cache
php artisan event:cache

exec "$@"
