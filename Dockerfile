# THIS IS BASE IMAGE
FROM php:8.1-cli-alpine

RUN apk add --no-cache git

RUN git config --global --add safe.directory *

# directory inside docker
WORKDIR /splitter

# make local content available inside docker - copies to /splitter
COPY . .

# see https://nickjanetakis.com/blog/docker-tip-86-always-make-your-entrypoint-scripts-executable
ENTRYPOINT ["php", "/splitter/entrypoint.php"]
