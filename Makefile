# import config.
cnf ?= .env
include $(cnf)
export $(shell sed 's/=.*//' $(cnf))

# get the latest SHA
GIT_HASH = $(shell git log -n 1 | head -n 1 | awk -F ' ' '{print $$2}' )


.PHONY: help

help: ## This help.
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

.DEFAULT_GOAL := help


# DOCKER TASKS
# Build the container
build: ## Build the container
	docker build -t $(APP_NAME) .

run: ## Run the container with .env
	docker run --rm --mount src=${PWD},target=/splitter,type=bind --env-file=.env --env GITHUB_SHA=$(GIT_HASH) --name="$(APP_NAME)" $(APP_NAME) 

start: build run ## Build & Run the container with .env

clean: ## Stop the running container if running and remove it.
	curl -X DELETE https://$(INPUT_USER_NAME):$(GITHUB_TOKEN)@api.github.com/repos/$(INPUT_REPOSITORY_ORGANIZATION)/$(INPUT_REPOSITORY_NAME)

test: clean run ## Run and cleanup afterwards, for rapid testing.

hash: ## test wether the git hash is correct
	@echo COMMIT HASH IS : \"$(GIT_HASH)\"
	@echo 
	@git log -n 1

