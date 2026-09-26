FROM php:8.2-cli

RUN apt-get update && apt-get install -y libpq-dev git unzip curl \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts
COPY . .
RUN composer dump-autoload --optimize
