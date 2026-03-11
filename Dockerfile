FROM php:8.2-apache
RUN apt-get update && apt-get install -y libpq-dev
RUN docker-php-ext-install pdo pdo_pgsql

# Composer telepítése
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /var/www/html/

# PHPMailer telepítése
RUN cd /var/www/html && composer install --no-dev --optimize-autoloaderFROM php:8.2-apache
RUN apt-get update && apt-get install -y libpq-dev
RUN docker-php-ext-install pdo pdo_pgsql
COPY . /var/www/html/
