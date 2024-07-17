# THIS IS BASE IMAGE
FROM php:8.0-cli

# Update package lists and hold the less package to prevent fail
RUN apt-get update -y && \
    apt-mark hold less && \
    apt-get install -y git

# directory inside docker
WORKDIR /splitter

# make local content available inside docker - copies to /splitter
COPY . .

# see https://nickjanetakis.com/blog/docker-tip-86-always-make-your-entrypoint-scripts-executable
ENTRYPOINT ["php", "/splitter/entrypoint.php"]
