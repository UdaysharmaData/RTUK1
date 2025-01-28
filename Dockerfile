FROM 733368872314.dkr.ecr.eu-west-2.amazonaws.com/sma-php-base:api

# Set working directory
WORKDIR /var/www/html

RUN rm -rf /var/www/html/*

COPY . /var/www/html

RUN composer update && composer install && composer dump-autoload

RUN php artisan passport:keys

RUN chown -R www-data:www-data /var/www/html

