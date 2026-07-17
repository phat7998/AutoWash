FROM php:8.2-cli

RUN set -eux \
    && apt-get update \
    && apt-get install -y git unzip zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app

RUN composer install --no-interaction --prefer-dist

CMD ["composer", "test"]
