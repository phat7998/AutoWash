#!/bin/sh

set -eu

install -d -o www-data -g www-data -m 0750 \
    /var/www/html/storage/logs \
    /var/www/html/storage/uploads

exec docker-php-entrypoint apache2-foreground
