FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN docker-php-ext-install pdo_mysql \
    && a2enmod headers rewrite \
    && echo "date.timezone=Asia/Ho_Chi_Minh" > /usr/local/etc/php/conf.d/autowash.ini \
    && echo "expose_php=Off" >> /usr/local/etc/php/conf.d/autowash.ini \
    && echo "ServerName localhost" > /etc/apache2/conf-available/zz-autowash.conf \
    && echo "ServerTokens Prod" >> /etc/apache2/conf-available/zz-autowash.conf \
    && a2enconf zz-autowash \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
