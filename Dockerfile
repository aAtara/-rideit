FROM php:8.2-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar mod_rewrite para .htaccess
RUN a2enmod rewrite

# Configurar Apache para permitir .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copiar archivos del proyecto
COPY . /var/www/html/

# Crear carpeta uploads con permisos correctos
RUN mkdir -p /var/www/html/uploads && chmod 755 /var/www/html/uploads

# Exponer puerto
EXPOSE 80
