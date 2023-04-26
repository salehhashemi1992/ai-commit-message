FROM php:8.1-cli

COPY . /app

WORKDIR /app

RUN curl -sS https://getcomposer.org/installer | php \
    && php composer.phar install

ENTRYPOINT ["php", "/app/main.php"]
