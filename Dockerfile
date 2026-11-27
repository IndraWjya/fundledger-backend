FROM php:8.4-cli-alpine

WORKDIR /var/www/html

# Ganti repository Alpine ke mirror HTTP untuk mencegah kendala TLS di Docker Desktop Windows
# dan gunakan mariadb-client (pengganti mysql-client di Alpine modern)
RUN sed -i 's/https/http/' /etc/apk/repositories \
    && apk update \
    && apk add --no-cache \
        curl \
        git \
        libzip-dev \
        zip \
        unzip \
        mariadb-client \
    && docker-php-ext-install pdo pdo_mysql bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-req=php+

EXPOSE 8000

CMD ["sh", "-c", "php artisan config:clear && php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=8000"]
