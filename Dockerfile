# Usa una imagen base de PHP-FPM con Alpine Linux (ligera)
FROM php:8.2-fpm-alpine

# Instala dependencias del sistema necesarias para varias extensiones
# y herramientas. Asegúrate de que estén *antes* de docker-php-ext-install
RUN apk add --no-cache \
    nginx \
    git \
    curl \
    supervisor \
    # Dependencias para PDO_PGSQL
    postgresql-dev \
    # Dependencias para Zip
    libzip-dev \
    # Dependencias para GD
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    # Dependencias para Intl
    icu-dev \
    # Dependencias generales para PHP
    oniguruma-dev \
    sqlite-dev \
    libc-dev \
    $PHPIZE_DEPS # Para compilar extensiones que no vienen con PHP

# Instala Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instala extensiones de PHP en grupos o individualmente
# Esto es más robusto que instalar todas de golpe
RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    zip \
    gd \
    intl \
    bcmath \
    ctype \
    json \
    mbstring \
    openssl \
    tokenizer \
    xml

# Elimina dependencias de construcción y limpia caché APK para reducir tamaño de imagen
RUN apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

# Configura Nginx para Laravel
COPY ./nginx.conf /etc/nginx/nginx.conf

# Copia la configuración de PHP-FPM para PHP 8.2
COPY ./php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Copia la configuración de Supervisor para correr PHP-FPM y Nginx
COPY ./supervisord.conf /etc/supervisord.conf

# Establece el directorio de trabajo
WORKDIR /app

# Copia el código de la aplicación
COPY . /app

# Instala dependencias de Composer después de copiar el código
# para aprovechar el caché de capas de Docker si composer.json no cambia
RUN composer install --no-dev --optimize-autoloader

# Ejecuta migraciones y genera la APP_KEY (solo una vez en la construcción de la imagen)
# Esto es más para entorno de desarrollo/CI. Para producción, es mejor que las migraciones
# se ejecuten como parte del comando de inicio si quieres un despliegue sin downtime
# y que APP_KEY sea una variable de entorno de Render.
# Si APP_KEY es una variable de entorno de Render, este comando NO es necesario aquí.
# Si las migraciones las haces con un "Job" o "Build Command" en Render, tampoco aquí.
# Dejémoslo si es tu única forma de hacerlo.
# RUN php artisan migrate --force && php artisan key:generate

# Otorga permisos adecuados al directorio storage y bootstrap/cache
# Esto es CRÍTICO para Laravel
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Exponer el puerto 80 (Nginx)
EXPOSE 80

# Comando para iniciar Nginx y PHP-FPM usando Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]