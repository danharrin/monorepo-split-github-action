FROM php:8.0-fpm

RUN apt-get update -y
RUN apt-get install git -y

# directory inside docker
WORKDIR /project

# make local content available inside docker - copies to /project
COPY . .

# see https://nickjanetakis.com/blog/docker-tip-86-always-make-your-entrypoint-scripts-executable
ENTRYPOINT ["/project/entrypoint.sh"]
