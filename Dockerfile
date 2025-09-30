FROM php:8.3-cli-alpine

# Install build/runtime deps via apk (no apt)
RUN set -eux; \
    apk add --no-cache \
      git unzip icu-dev oniguruma-dev libzip-dev \
      libpng-dev freetype-dev jpeg-dev mariadb-client; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install pdo_mysql mbstring bcmath gd zip intl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Run Laravel on port 9000 inside the container
CMD ["sh", "-lc", "composer install && php -S 0.0.0.0:9000 -t public"]