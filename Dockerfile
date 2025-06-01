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
    build-base

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN docker-php-ext-configure gd --with-jpeg --with-webp

# INSTALACIÓN DE EXTENSIONES - ELIMINANDO 'json'
RUN docker-php-ext-install -j$(nproc) pdo_mysql
RUN docker-php-ext-install -j$(nproc) pdo_pgsql
RUN docker-php-ext-install -j$(nproc) zip
RUN docker-php-ext-install -j$(nproc) gd
RUN docker-php-ext-install -j$(nproc) intl
RUN docker-php-ext-install -j$(nproc) bcmath
RUN docker-php-ext-install -j$(nproc) ctype
# REMOVIDA: RUN docker-php-ext-install -j$(nproc) json
RUN docker-php-ext-install -j$(nproc) mbstring
RUN docker-php-ext-install -j$(nproc) openssl
RUN docker-php-ext-install -j$(nproc) tokenizer
RUN docker-php-ext-install -j$(nproc) xml

# Limpia dependencias de construcción y caché APK
RUN apk del $PHPIZE_DEPS build-base \
    && rm -rf /var/cache/apk/*

COPY ./nginx.conf /etc/nginx/nginx.conf

COPY ./php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

COPY ./supervisord.conf /etc/supervisord.conf

WORKDIR /app

COPY . /app

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]