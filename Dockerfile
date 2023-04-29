FROM php:8.1-cli

# Install the required dependencies
RUN apt-get update && \
    apt-get install -y git zip unzip && \
    rm -rf /var/lib/apt/lists/*

COPY . /app
WORKDIR /app

# Install composer and project dependencies
RUN curl -sS https://getcomposer.org/installer | php && \
    php composer.phar install

ENTRYPOINT ["php", "/app/main.php"]
