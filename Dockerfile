FROM yiisoftware/yii2-php:8.2-apache

RUN apt-get update && apt-get install -y cron

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-interaction --prefer-dist

EXPOSE 80

# executed every hour
RUN echo "* * * * * /usr/local/bin/php /var/www/html/yii check-status/statistics >> /var/www/html/runtime/logs/cron.log 2>&1" | crontab -

CMD ["apache2-foreground"]