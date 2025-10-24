FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev \
    libxml2-dev sqlite3 libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite zip gd

RUN a2enmod rewrite

WORKDIR /var/www/html

COPY . .


RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/database \
    && chmod -R 777 /var/www/html/database

EXPOSE 80

CMD ["apache2-foreground"]