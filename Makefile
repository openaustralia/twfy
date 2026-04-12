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
	@echo "  test-docker    Run all tests in Docker with DB (simplest method)"
	@echo "  test-coverage  Run all tests with code coverage reports"
	@echo "  test-coverage-docker  Run coverage inside Docker (no host PHP extensions needed)"

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

test-coverage: vendor/autoload.php
	@if ! php -m | grep -Eq 'xdebug|pcov'; then \
		echo "Coverage requires Xdebug or PCOV to be enabled in PHP."; \
		echo "For GitHub Actions, set setup-php coverage to 'xdebug'."; \
		exit 1; \
	fi
	DB_HOST=$(TEST_DB_HOST) DB_USER=$(TEST_DB_USER) DB_PASSWORD=$(TEST_DB_PASSWORD) DB_NAME=$(TEST_DB_NAME) XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text --coverage-clover=coverage/clover.xml --coverage-html=coverage/html

test-coverage-docker:
	docker compose up -d mysql
	docker compose run --rm -e DB_HOST=mysql -e DB_USER=$(TEST_DB_USER) -e DB_PASSWORD=$(TEST_DB_PASSWORD) -e DB_NAME=$(TEST_DB_NAME) -e XDEBUG_MODE=coverage -v $(CURDIR):/app -w /app webhost bash -lc "php -m | grep -qi xdebug || { echo 'xdebug is missing in twfy-app. Run make docker-build first.'; exit 1; }; ./vendor/bin/phpunit --coverage-text --coverage-clover=coverage/clover.xml --coverage-html=coverage/html"

test-docker:
	docker compose up -d mysql
	docker compose run --rm -e DB_HOST=mysql -e DB_USER=$(TEST_DB_USER) -e DB_PASSWORD=$(TEST_DB_PASSWORD) -e DB_NAME=$(TEST_DB_NAME) -v $(CURDIR):/app -w /app webhost bash -lc "composer install --no-interaction --prefer-dist && ./vendor/bin/phpunit"

