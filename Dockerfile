# Usa una imagen base de PHP-FPM con Alpine Linux (ligera)
FROM php:8.2-fpm-alpine

# Actualiza los índices de paquetes y instala dependencias comunes y de construcción
# Asegúrate de que $PHPIZE_DEPS esté al inicio para preparar el entorno de compilación
RUN apk update --no-cache && apk add --no-cache \
    nginx \
    git \
    curl \
    supervisor \
    # Dependencias de construcción para PHP
    $PHPIZE_DEPS \
    # Dependencias para PDO_PGSQL y PDO_MYSQL
    postgresql-dev \
    mysql-client \
    # Dependencias para Zip
    libzip-dev \
    # Dependencias para GD
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    # Dependencias para Intl
    icu-dev \
    # Dependencias para Bcrypt, JSON, Mbstring, OpenSSL, XML
    oniguruma-dev \
    libxml2-dev \
    sqlite-dev \
    # Otras dependencias generales de C/C++
    libc-dev \
    build-base # Proporciona herramientas de construcción como make, gcc, etc.

# Instala Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instala extensiones de PHP en grupos o individualmente
# Esto es más robusto y ayuda a identificar qué extensión falla si una falla
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
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
    xml \
    # Añade cualquier otra extensión que uses
    # ej. exif, opcache si necesitas
    # exif \
    # opcache

# Limpia dependencias de construcción y caché APK para reducir tamaño de imagen
RUN apk del $PHPIZE_DEPS build-base \
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
RUN composer install --no-dev --optimize-autoloader

# Otorga permisos adecuados al directorio storage y bootstrap/cache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Exponer el puerto 80 (Nginx)
EXPOSE 80

# Comando para iniciar Nginx y PHP-FPM usando Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]