# Usa una imagen base de PHP-FPM con Alpine Linux (ligera)
FROM php:8.2-fpm-alpine

# Instala dependencias del sistema necesarias
RUN apk add --no-cache \
    nginx \
    git \
    curl \
    libpq \
    libzip-dev \
    sqlite-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    icu-dev \
    postgresql-dev \
    oniguruma-dev \
    supervisor

# Instala extensiones de PHP necesarias (ajusta según tus necesidades)
# PDO MySQL/PostgreSQL, GD, Zip, Intl, BCMath, Ctype, JSON, Mbstring, OpenSSL, Tokenizer, XML
RUN docker-php-ext-install -j$(nproc) pdo_mysql pdo_pgsql zip gd intl bcmath ctype json mbstring openssl tokenizer xml

# Instala Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

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

# Otorga permisos adecuados al directorio storage y bootstrap/cache
# Esto es CRÍTICO para Laravel
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Exponer el puerto 80 (Nginx)
EXPOSE 80

# Comando para iniciar Nginx y PHP-FPM usando Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]