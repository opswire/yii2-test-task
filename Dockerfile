FROM yiisoftware/yii2-php:8.2-apache

RUN apt-get update && apt-get install -y cron

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . .

EXPOSE 80

# executed every hour
RUN echo "* * * * * /usr/local/bin/php /app/yii check-status/statistics >> /app/runtime/logs/cron.log 2>&1" | crontab -

CMD ["apache2-foreground"]