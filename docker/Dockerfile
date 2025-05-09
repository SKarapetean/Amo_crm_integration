FROM php:8.2-apache

RUN a2enmod rewrite

#RUN #docker-php-ext-install pdo pdo_mysql

COPY vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html/

RUN chown -R www-data:www-data /var/www/html
