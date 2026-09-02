FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    libzip-dev \
    netcat-openbsd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . /var/www/html
COPY docker/php/dev.ini /usr/local/etc/php/conf.d/99-northlink-dev.ini
COPY docker-entrypoint.sh /usr/local/bin/

RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000 8000

ENTRYPOINT ["docker-entrypoint.sh"]
