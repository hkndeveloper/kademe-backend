# Build Stage
FROM php:8.2-fpm-alpine

# Dependencies
RUN apk add --no-cache \
    nginx \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    icu-dev

# PHP Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql zip mbstring bcmath intl

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# App Directory
WORKDIR /var/www/html

# Copy Files
COPY . .

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Install PHP Dependencies
RUN composer install --no-dev --optimize-autoloader

# Nginx Config
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf

# Start Script
COPY ./docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]
