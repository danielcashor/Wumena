# Usa una imagen base de PHP-FPM basada en Debian (más completa)
FROM php:8.2-fpm

# Instala dependencias del sistema generales y Nginx
RUN apt-get update --yes --no-install-recommends && apt-get install -y \
    nginx \
    git \
    curl \
    supervisor \
    build-essential \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# === INSTALACIÓN DE DEPENDENCIAS DE DESARROLLO DE PHP (UNA POR UNA) ===
RUN apt-get update --yes --no-install-recommends && apt-get install -y libpq-dev && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN apt-get update --yes --no-install-recommends && apt-get install -y default-libmysqlclient-dev && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN apt-get update --yes --no-install-recommends && apt-get install -y libzip-dev && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN apt-get update --yes --no-install-recommends && apt-get install -y libpng-dev && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN apt-get update --yes --no-install-recommends && apt-get install -y libjpeg62-turbo-dev && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN apt-get update --yes --no-install-recommends && apt-get install -y libwebp-dev && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN apt-get update --yes --no-install-recommends && apt-get install -y libicu-dev && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN apt-get update --yes --no-install-recommends && apt-get install -y libonig-dev && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN apt-get update --yes --no-install-recommends && apt-get install -y libxml2-dev && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN apt-get update --yes --no-install-recommends && apt-get install -y libssl-dev && apt-get clean && rm -rf /var/lib/apt/lists/*

# Limpieza final de apt-get
RUN rm -rf /tmp/* /var/tmp/* /usr/share/doc /usr/share/man


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

# ¡CAMBIO CLAVE AQUÍ! Copia zzz-www.conf al directorio por defecto de pools de PHP-FPM.
# Así, php-fpm lo encontrará automáticamente sin necesidad de especificar --fpm-config o --y.
COPY ./zzz-www.conf /usr/local/etc/php-fpm.d/zzz-www.conf
# Ajusta permisos si es necesario, aunque en /usr/local/etc/php-fpm.d suele no serlo tanto como en /etc/php
# RUN chmod 644 /usr/local/etc/php-fpm.d/zzz-www.conf && chown www-data:www-data /usr/local/etc/php-fpm.d/zzz-www.conf
# La línea de chmod/chown anterior probablemente no es necesaria si el user/group ya son www-data.
# Si sigue fallando por permisos después de este cambio, volvemos a poner el chmod/chown.

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