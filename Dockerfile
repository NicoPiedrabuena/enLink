FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache $PHPIZE_DEPS git icu-dev libzip-dev oniguruma-dev sqlite-dev freetype-dev libjpeg-turbo-dev libpng-dev su-exec \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql pdo_sqlite bcmath gd intl opcache zip \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

COPY composer.json composer.lock ./

FROM base AS production-dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM base AS development-dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY --from=production-dependencies /var/www/html/vendor ./vendor
COPY . .
RUN npm run build

FROM base AS test
COPY --from=development-dependencies /var/www/html/vendor ./vendor
COPY . .
RUN cp .env.example .env \
    && composer dump-autoload --optimize
CMD ["php", "artisan", "test"]

FROM base AS production
COPY --from=production-dependencies /var/www/html/vendor ./vendor
COPY . .
COPY --from=frontend /app/public/build ./public/build
RUN composer dump-autoload --no-dev --optimize \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/php/entrypoint.sh

ENTRYPOINT ["docker/php/entrypoint.sh"]
CMD ["php-fpm"]

FROM nginx:1.31-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/html/public
COPY --from=frontend /app/public/build /var/www/html/public/build
