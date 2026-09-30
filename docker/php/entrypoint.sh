#!/bin/sh
set -e

# Named volumes preserve their previous ownership between image rebuilds.
# Laravel's web and worker processes must always be able to write here.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

if [ "$1" != "php-fpm" ]; then
    exec su-exec www-data "$@"
fi

exec "$@"
