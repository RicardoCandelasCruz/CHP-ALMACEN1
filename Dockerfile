# Imagen base de PHP con Apache
FROM php:8.2-apache

# Instalar dependencias del sistema y extensiones de PostgreSQL
RUN apt-get update \
    && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && docker-php-ext-enable pdo_pgsql pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copiar el código de la aplicación al contenedor
COPY . /var/www/html/

# Dar permisos a Apache
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto 80
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]
