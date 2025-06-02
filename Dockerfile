# Usa una imagen base de PHP-FPM basada en Debian (más completa)
FROM php:8.2-fpm

# Instala dependencias del sistema usando apt-get
# Limpia la caché de apt-get para mantener la imagen pequeña
RUN apt-get update --yes --no-install-recommends && apt-get install -y \
    nginx \
    git \
    curl \
    supervisor \
    # Dependencias para PDO_PGSQL
    libpq-dev \
    # Dependencias para PDO_MYSQL
    libmysqlclient-dev \
    # Dependencias para Zip
    libzip-dev \
    # Dependencias para GD
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    # Dependencias para Intl
    libicu-dev \
    # Dependencias para Mbstring
    libonig-dev \
    # Dependencias para XML
    libxml2-dev \
    # Dependencias para Bcmath, Ctype, Json, Openssl, Tokenizer, Exif (ya suelen venir o son fáciles)
    # Algunas pueden necesitar paquetes dev si no están ya en la imagen base
    # (Ej. libssl-dev para openssl, aunque suele venir)
    libssl-dev \
    # Herramientas de compilación (build-essential es el equivalente a build-base en Debian)
    build-essential \
    # Limpieza final
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && rm -rf /usr/share/doc /usr/share/man # Reduce tamaño

# Instala Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instala y habilita extensiones de PHP.
# En imágenes no-Alpine, docker-php-ext-install suele funcionar bien para las que no son core.
# Las core como json, openssl, tokenizer, ctype, mbstring ya suelen venir pre-instaladas o habilitadas.
RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    zip \
    gd \
    intl \
    bcmath \
    mbstring \
    xml \
    # Si estas últimas dan problemas, puedes quitarlas y ver si están ya habilitadas
    # ctype \
    # json \
    # openssl \
    # tokenizer \
    # exif # Si la necesitas
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    # Habilita cualquier extensión que necesites y que no se habilite automáticamente
    # Por ejemplo, si openssl está ahí pero no habilitado
    # && docker-php-ext-enable openssl \
    # && docker-php-ext-enable json # Etc.


# Copia la configuración de Nginx
COPY ./nginx.conf /etc/nginx/nginx.conf

# Copia la configuración de PHP-FPM (AHORA EN UNA RUTA DIFERENTE)
# En imágenes Debian, los pools de PHP-FPM suelen ir en /etc/php/<version>/fpm/pool.d/
COPY ./zzz-www.conf /etc/php/8.2/fpm/pool.d/zzz-www.conf

# Copia la configuración de Supervisor
COPY ./supervisord.conf /etc/supervisord.conf

# Establece el directorio de trabajo
WORKDIR /app

# Copia el código de la aplicación
COPY . /app

# Instala dependencias de Composer
RUN composer install --no-dev --optimize-autoloader

# Otorga permisos adecuados
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Exponer el puerto 80 (Nginx)
EXPOSE 80

# Comando para iniciar Nginx y PHP-FPM usando Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]