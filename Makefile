# PLATFORM=linux/arm64,linux/amd64
PLATFORM=linux/amd64

TWFY_HTTP_PORT ?= 80
TWFY_MYSQL_PORT ?= 3306

TEST_DB_HOST ?= 127.0.0.1:$(TWFY_MYSQL_PORT)
TEST_DB_USER ?= twfyuser
TEST_DB_PASSWORD ?= twfypass
TEST_DB_NAME ?= twfy

all:
	@echo "Available targets:"
	@echo "  docker-build                        Build the Docker image for the application"
	@echo "  docker-run                          Run the Docker container for the application"
	@echo "  lint                                Run linting on the www directory"
	@echo "  install                             Install Composer dependencies"
	@echo "  test [TEST_ARGS=...]                Run PHPUnit tests"
	@echo "  test-all [TEST_ARGS=...]            Run all PHPUnit tests including DB integration"
	@echo "  test-docker [TEST_ARGS=...]         Run all tests in Docker with DB (simplest method)"
	@echo "  test-coverage [TEST_ARGS=...]       Run all tests with code coverage reports"
	@echo "  test-coverage-docker [TEST_ARGS=...] Run coverage inside Docker (no host PHP extensions needed)"
	@echo "  phpcs [PHPCS_ARGS=...]              Run coding standards check (summary)"
	@echo "  phpcs-verbose [PHPCS_ARGS=...]      Run coding standards check (verbose)"
	@echo ""
	@echo "Extra args:"
	@echo "  TEST_ARGS      Extra args for phpunit targets e.g."
	@echo "                   --display-skipped      show skip reasons"
	@echo "                   --display-all-issues   show all issues (deprecations, notices etc)"
	@echo "                   --debug                show each test as it runs"
	@echo "                   --filter ClassName     run a single test class"
	@echo "                   --filter testMethod    run a single test method"
	@echo "  PHPCS_ARGS     Extra args for phpcs targets e.g."
	@echo "                   --report=full          show each violation in context"
	@echo "                   www/path/to/file.php   check a specific file"
	@echo " TWFY_HTTP_PORT, TWFY_MYSQL_PORT - override default host ports used in docker-compose.yml (80 and 3306)"

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
	docker compose up -d $(DOCKER_ARGS)

docker: docker-build docker-run

lint:
	find -L www scripts -iregex '.*\.php$$' -print0 | xargs -0 -n 1 -P 4 php -l

lint-ci: lint

phpcs:
	./vendor/bin/phpcs --standard=phpcs.xml --tab-width=4 --report=summary www scripts $(PHPCS_ARGS)

phpcs-ci phpcs-verbose:
	./vendor/bin/phpcs --standard=phpcs.xml --tab-width=4 www scripts $(PHPCS_ARGS)

install:
	composer install --no-interaction --prefer-dist

test: vendor/autoload.php
	./vendor/bin/phpunit $(TEST_ARGS)

test-all: vendor/autoload.php
	DB_HOST=$(TEST_DB_HOST) DB_USER=$(TEST_DB_USER) DB_PASSWORD=$(TEST_DB_PASSWORD) DB_NAME=$(TEST_DB_NAME) ./vendor/bin/phpunit $(TEST_ARGS)

install-xdebug:
	@if mise exec -- php -m | grep -Eq 'xdebug|pcov'; then \
		echo "xdebug or pcov already installed"; \
	else \
		mise exec -- pecl install xdebug && \
		SCAN_DIR=$$(mise exec -- php --ini | grep "Scan for additional" | awk -F': ' '{print $$2}') && \
		echo "zend_extension=xdebug.so" > "$$SCAN_DIR/xdebug.ini" && \
		echo "Done. Verify: mise exec -- php -m | grep xdebug"; \
	fi

test-coverage: vendor/autoload.php
	@if ! php -m | grep -Eq 'xdebug|pcov'; then \
		echo "Coverage requires Xdebug or PCOV to be enabled in PHP (use make install-xdisplay)."; \
		echo "For GitHub Actions, set setup-php coverage to 'xdebug'."; \
		exit 1; \
	fi
	DB_HOST=$(TEST_DB_HOST) DB_USER=$(TEST_DB_USER) DB_PASSWORD=$(TEST_DB_PASSWORD) DB_NAME=$(TEST_DB_NAME) XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text --coverage-clover=coverage/clover.xml --coverage-html=coverage/html $(TEST_ARGS) && \
	echo && echo "Open coverage/html/index.html to see the detailed coverage report"

test-coverage-docker:
	docker compose up -d
	docker compose run --rm -e DB_HOST=mysql -e DB_USER=$(TEST_DB_USER) -e DB_PASSWORD=$(TEST_DB_PASSWORD) -e DB_NAME=$(TEST_DB_NAME) -e XDEBUG_MODE=coverage -v $(CURDIR):/app -w /app webhost bash -lc "php -m | grep -qi xdebug || { echo 'xdebug is missing in twfy-app. Run make docker-build first.'; exit 1; }; ./vendor/bin/phpunit --coverage-text --coverage-clover=coverage/clover.xml --coverage-html=coverage/html $(TEST_ARGS)"

test-docker:
	docker compose up -d mysql
	docker compose run --rm -e DB_HOST=mysql -e DB_USER=$(TEST_DB_USER) -e DB_PASSWORD=$(TEST_DB_PASSWORD) -e DB_NAME=$(TEST_DB_NAME) -v $(CURDIR):/app -w /app webhost bash -lc "composer install --no-interaction --prefer-dist && ./vendor/bin/phpunit"

not_on_server:
	@if echo "$(shell pwd)" | grep -qE '/(current|releases)' ; then \
			echo "ERROR: This should not be run on a production/staging server!"; \
			exit 1; \
	fi

setup_db: not_on_server
	mysql -h 127.0.0.1 -P $(TWFY_MYSQL_PORT) -u $(TEST_DB_USER) -p$(TEST_DB_PASSWORD) -e "CREATE DATABASE IF NOT EXISTS $(TEST_DB_NAME)"
	mysql -h 127.0.0.1 -P $(TWFY_MYSQL_PORT) -u $(TEST_DB_USER) -p$(TEST_DB_PASSWORD) $(TEST_DB_NAME) < db/schema.sql
