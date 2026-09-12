FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    git curl unzip libpng-dev libjpeg-turbo-dev freetype-dev \
    oniguruma-dev libxml2-dev zip libzip-dev icu-dev mysql-client \
    linux-headers $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

RUN pecl install redis && docker-php-ext-enable redis

RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

EXPOSE 9000
CMD ["php-fpm"]