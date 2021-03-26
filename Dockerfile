FROM php:8.0-fpm-alpine

RUN apk add --no-cache git

# make local content available inside docker
COPY . .

# see https://nickjanetakis.com/blog/docker-tip-86-always-make-your-entrypoint-scripts-executable
ENTRYPOINT ["/bin/sh", "-c", "./entrypoint.sh"]
