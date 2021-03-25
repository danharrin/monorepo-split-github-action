FROM php:8.0-fpm-alpine

RUN apk add --no-cache git

# make local content available inside docker
COPY src src
COPY entrypoint.sh /entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
