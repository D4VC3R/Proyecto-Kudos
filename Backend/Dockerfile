# 1. Usar la imagen oficial de FrankenPHP con PHP 8.4
FROM dunglas/frankenphp:1.4-php8.4-bookworm

# 2. Declarar puerto base y variables de entorno seguras
ENV PORT=8080 \
    APP_ENV=production \
    APP_DEBUG=false

# 3. Instalar dependencias del sistema operativo
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    git \
    && curl -sS https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o /usr/local/bin/install-php-extensions \
    && chmod +x /usr/local/bin/install-php-extensions

# 4. Compilar e instalar TODAS las extensiones nativas necesarias
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

# 5. Instalar Composer de forma limpia
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Establecer el directorio de trabajo
WORKDIR /app

# 7. Copiar el código fuente del backend al contenedor
COPY . /app

# 8. Instalar las dependencias de Composer optimizadas
RUN composer install --no-dev --no-scripts --optimize-autoloader

# 9. Limpiar caché del sistema operativo para reducir peso
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# 10. Configurar permisos cruciales para que Laravel pueda escribir
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache && \
    chmod -R 775 /app/storage /app/bootstrap/cache

# 11. Exponer el puerto
EXPOSE 8080

# 12. Comando de arranque en formato Shell (Vital: permite inyectar el $PORT dinámico)
CMD frankenphp php-server --listen :${PORT:-8080} --root public/