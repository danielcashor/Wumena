FROM php:8.2-fpm-alpine

RUN apk update --no-cache && apk add --no-cache \
    nginx \
    git \
    curl \
    supervisor \
    $PHPIZE_DEPS \
    postgresql-dev \
    mysql-client \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    sqlite-dev \
    libc-dev \
    build-base \
    openssl-dev

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# REMOVIDAS TODAS LAS LÍNEAS DE docker-php-ext-configure y docker-php-ext-install aquí
# La hipótesis es que estas extensiones ya están incluidas o habilitadas en la imagen base
# Si después del build vemos que faltan, las añadiremos de otra forma.

# Limpia dependencias de construcción y caché APK
RUN apk del $PHPIZE_DEPS build-base \
    && rm -rf /var/cache/apk/*

COPY ./nginx.conf /etc/nginx/nginx.conf

COPY ./zzz-www.conf /usr/local/etc/php-fpm.d/zzz-www.conf

COPY ./supervisord.conf /etc/supervisord.conf

WORKDIR /app

COPY . /app

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]