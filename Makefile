# PLATFORM=linux/arm64,linux/amd64
PLATFORM=linux/amd64

TEST_DB_HOST ?= 127.0.0.1
TEST_DB_USER ?= twfyuser
TEST_DB_PASSWORD ?= twfypass
TEST_DB_NAME ?= twfy

all:
	@echo "Available targets:"
	@echo "  docker-build   Build the Docker image for the application"
	@echo "  docker-run     Run the Docker container for the application"
	@echo "  lint           Run linting on the www directory"
	@echo "  install        Install Composer dependencies"
	@echo "  test           Run PHPUnit tests"
	@echo "  test-all       Run all PHPUnit tests including DB integration"

docker-build:
	docker buildx build \
		--build-arg VCS_REF=`git rev-parse --short HEAD` \
		--build-arg VCS_URL=`git config --get remote.origin.url | sed 's#git@github.com:#https://github.com/#'` \
		--build-arg BUILD_DATE=`date -u +"%Y-%m-%dT%H:%M:%SZ"` \
		--no-cache \
		--load \
		--platform=$(PLATFORM) \
		-t twfy-app \
		.

docker-run:
	docker compose up -d

docker: docker-build docker-run

lint:
	find -L www scripts -iregex '.*\.php$$' -print0 | xargs -0 -n 1 -P 4 php -l

lint-ci: lint

phpcs:
	./vendor/bin/phpcs --standard=phpcs.xml --tab-width=4 --report=summary www scripts

phpcs-ci phpcs-verbose:
	./vendor/bin/phpcs --standard=phpcs.xml --tab-width=4 www scripts

install:
	composer install --no-interaction --prefer-dist

test: vendor/autoload.php
	./vendor/bin/phpunit

test-all: vendor/autoload.php
	DB_HOST=$(TEST_DB_HOST) DB_USER=$(TEST_DB_USER) DB_PASSWORD=$(TEST_DB_PASSWORD) DB_NAME=$(TEST_DB_NAME) ./vendor/bin/phpunit

