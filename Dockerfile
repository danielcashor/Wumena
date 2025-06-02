# Usa una imagen base de PHP-FPM basada en Debian (más completa)
FROM php:8.2-fpm

# Instala dependencias del sistema generales y Nginx
RUN apt-get update --yes --no-install-recommends && apt-get install -y \
    nginx \
    git \
    curl \
    supervisor \
    build-essential \
    # Limpieza parcial aquí, la final se hace después de todas las instalaciones
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instala las dependencias de desarrollo de PHP
RUN apt-get update --yes --no-install-recommends && apt-get install -y \
    libpq-dev \
    libmysqlclient-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo8-dev \
    libwebp-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    # Limpieza final de apt-get
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && rm -rf /usr/share/doc /usr/share/man # Reduce tamaño

# Instala Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instala y habilita extensiones de PHP.
RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    zip \
    gd \
    intl \
    bcmath \
    mbstring \
    xml \
    && docker-php-ext-configure gd --with-jpeg --with-webp


# Copia la configuración de Nginx
COPY ./nginx.conf /etc/nginx/nginx.conf

# Copia la configuración de PHP-FPM (ruta para Debian)
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