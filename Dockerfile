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
# Es más eficiente agrupar apt-get installs si las dependencias son similares,
# pero tu método una por una para asegurar que cada una se procesa es válido.
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

# Copia la configuración de PHP-FPM (ruta para Debian)
COPY ./zzz-www.conf /etc/php/8.2/fpm/pool.d/zzz-www.conf
RUN chmod 644 /etc/php/8.2/fpm/pool.d/zzz-www.conf && chown www-data:www-data /etc/php/8.2/fpm/pool.d/zzz-www.conf

# === ¡NUEVAS LÍNEAS DE DEPURACIÓN AQUÍ! ===
# Instala 'locales-all' que a veces es necesario para la correcta ejecución de algunos programas (raro, pero no hace daño)
RUN apt-get update && apt-get install -y locales locales-all && rm -rf /var/lib/apt/lists/*
# Muestra dónde está php-fpm en el PATH del contenedor (lo verás en los logs de BUILD)
RUN which php-fpm || echo "php-fpm no se encontró en el PATH. Error crítico."
# Prueba la configuración de php-fpm (si esto falla, el error está en zzz-www.conf o sus permisos)
RUN php-fpm -t || echo "La configuración de php-fpm falló la prueba. Revisa zzz-www.conf."
# ==========================================

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