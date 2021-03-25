FROM php:8.0-fpm-alpine

RUN apk add --no-cache git

# composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_MEMORY_LIMIT=-1
COPY composer.* ./
RUN composer install --prefer-dist --no-progress

# make local content available inside docker
COPY . .

ENTRYPOINT ["/entrypoint.sh"]
