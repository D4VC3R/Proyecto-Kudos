# ==========================================
# Etapa 1: Construcción (Builder)
# ==========================================
FROM composer:2.7 AS builder

WORKDIR /app

# Copiamos solo los archivos necesarios para resolver dependencias
COPY composer.json composer.lock ./

# Instalamos dependencias de producción limpias (sin dev)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copiamos el resto del código
COPY . .

# Generamos el autoloader optimizado
RUN composer dump-autoload --optimize --no-dev

# ==========================================
# Etapa 2: Imagen Final de Producción
# ==========================================
FROM php:8.4-fpm-bookworm

# 1. Instalación de Nginx, Supervisor y dependencias del sistema (GD, PostgreSQL, etc.)
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    curl \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libzip-dev \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Configuración de extensiones PHP (incluyendo GD para Intervention Image)
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

# 3. Instalación de Redis
RUN pecl install redis \
    && docker-php-ext-enable redis

# 4. Configuración de OPcache para máximo rendimiento en producción
RUN echo "opcache.enable=1\n\
opcache.memory_consumption=256\n\
opcache.interned_strings_buffer=16\n\
opcache.max_accelerated_files=10000\n\
opcache.validate_timestamps=0\n\
opcache.save_comments=1\n\
opcache.fast_shutdown=1" > /usr/local/etc/php/conf.d/opcache-recommended.ini

# 5. Configurar el directorio de trabajo
WORKDIR /var/www/html

# 6. Copiar el código compilado desde la etapa Builder
COPY --from=builder --chown=www-data:www-data /app /var/www/html

# 7. Permisos estrictos para directorios de escritura de Laravel
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 8. Configuración virtual de Nginx (comunicación con PHP-FPM)
RUN echo 'server {\n\
    listen 80;\n\
    server_name _;\n\
    root /var/www/html/public;\n\
    index index.php;\n\
    charset utf-8;\n\
    location / {\n\
        try_files $uri $uri/ /index.php?$query_string;\n\
    }\n\
    location = /favicon.ico { access_log off; log_not_found off; }\n\
    location = /robots.txt  { access_log off; log_not_found off; }\n\
    error_page 404 /index.php;\n\
    location ~ \.php$ {\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;\n\
        include fastcgi_params;\n\
        fastcgi_hide_header X-Powered-By;\n\
    }\n\
    location ~ /\.(?!well-known).* {\n\
        deny all;\n\
    }\n\
}' > /etc/nginx/sites-available/default

# 9. Configuración de Supervisor (gestiona Nginx y FPM en un solo contenedor)
RUN echo '[supervisord]\n\
nodaemon=true\n\
user=root\n\
logfile=/var/log/supervisor/supervisord.log\n\
pidfile=/var/run/supervisord.pid\n\
\n\
[program:php-fpm]\n\
command=php-fpm -F\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0\n\
\n\
[program:nginx]\n\
command=nginx -g "daemon off;"\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0' > /etc/supervisor/conf.d/supervisord.conf

# 10. Exponer puerto 80 (Railway lo mapeará automáticamente)
EXPOSE 80

# 11. Ejecutar Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]