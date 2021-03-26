FROM docker.pkg.github.com/symplify/monorepo-split-github-action/splitter:main

# directory inside docker
WORKDIR /project

# make local content available inside docker - copies to /project
COPY . .

# see https://nickjanetakis.com/blog/docker-tip-86-always-make-your-entrypoint-scripts-executable
ENTRYPOINT ["/splitter/entrypoint.sh"]
