# PLATFORM=linux/arm64,linux/amd64
PLATFORM=linux/amd64

TWFY_HTTP_PORT ?= 80
TWFY_MYSQL_PORT ?= 3306

TEST_DB_HOST ?= 127.0.0.1:$(MYSQL_HOST_PORT)
TEST_DB_USER ?= twfyuser
TEST_DB_PASSWORD ?= twfypass
TEST_DB_NAME ?= test_twfy
DEV_DB_NAME ?= twfy
SONAR_SCANNER ?= sonar-scanner
XAPIANDB ?= /app/shared/search/searchdb
XAPIANDB_LASTUPDATED ?= $(XAPIANDB)/../searchdb-lastupdated

.PHONY: help docker-build docker-run docker xapian-index-docker lint lint-perl lint-perl-ci lint-php lint-php-ci phpcs phpcs-ci phpcs-verbose phpcs-sonar sonar-ci dependencies install setup test test-all install-xdebug test-coverage test-coverage-docker docker-test-db-create

help:
	@echo "Available targets:"
	@echo "  docker                              Build docker image then run docker container"
	@echo "  docker-build                        Build the Docker image for the application"
	@echo "  docker-run                          Run the Docker container for the application"
	@echo "  docker-db-migrate                   Run pending Phinx migrations against the docker mysql"
	@echo "  docker-db-migrate-down [MIGRATION_TARGET=<version>]  Roll back last migration (or to version)"
	@echo "  docker-db-seed [SEEDER=<Name>]      Run Phinx seeders against the docker mysql (all, or a specific one)"
	@echo "  docker-dump-schema                  Dump docker mysql schema (no data) to db/schema.sql"
	@echo "  xapian-index-docker                Run Xapian indexing in Docker"
	@echo "  help                                Output this help"
	@echo "  lint                                Run lint-php and lint-perl on the www and scripts directories"
	@echo "  dependencies                        Install PHP (Composer) dependencies into ./vendor"
	@echo "  install                             Install Composer and script dependencies (alias of dependencies + run-with-lockfile)"
	@echo "  setup                               Install ubuntu packages required for development"
	@echo "  test [TEST_ARGS=...]                Run PHPUnit tests"
	@echo "  test-all [TEST_ARGS=...]            Run all PHPUnit tests including DB integration"
	@echo "  test-docker [TEST_ARGS=...]         Run all tests in Docker with DB (simplest method)"
	@echo "  test-coverage [TEST_ARGS=...]       Run all tests with code coverage reports"
	@echo "  test-coverage-docker [TEST_ARGS=...] Run coverage inside Docker (no host PHP extensions needed)"
	@echo "  phpcs [PHPCS_ARGS=...]              Run coding standards check (summary)"
	@echo "  phpcs-verbose [PHPCS_ARGS=...]      Run coding standards check (verbose)"
	@echo "  phpcs-sonar [PHPCS_ARGS=...]        Generate PHPCS checkstyle report for SonarQube"
	@echo "  sonar-ci [TEST_ARGS=... PHPCS_ARGS=...] Run coverage + PHPCS report + Sonar scanner"
	@echo "  scripts/run-with-lockfile           Compile lockfile utility (dev only)"
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
	@echo "  XAPIANDB, XAPIANDB_LASTUPDATED - override index and timestamp paths for xapian-index-docker"

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
	@echo "Site should now be available at http://localhost:$(TWFY_HTTP_PORT) and MySQL at http://localhost:$(TWFY_MYSQL_PORT)"

# Run pending Phinx migrations against the docker mysql service via the webhost container.
# Always dumps the resulting schema to db/schema.sql so it can be committed alongside the migration.
docker-db-migrate: vendor/autoload.php
	docker compose up -d mysql
	docker compose run --rm \
		-e DB_HOST=mysql -e DB_USER=$(TEST_DB_USER) -e DB_PASSWORD=$(TEST_DB_PASSWORD) -e DB_NAME=$(DEV_DB_NAME) \
		-v $(CURDIR):/app -w /app webhost ./vendor/bin/phinx migrate -c phinx.php
	$(MAKE) docker-dump-schema

# Roll back the last Phinx migration (or pass MIGRATION_TARGET=<version> to roll back to a specific version).
# Always dumps the resulting schema to db/schema.sql.
docker-db-migrate-down: vendor/autoload.php
	docker compose up -d mysql
	docker compose run --rm \
		-e DB_HOST=mysql -e DB_USER=$(TEST_DB_USER) -e DB_PASSWORD=$(TEST_DB_PASSWORD) -e DB_NAME=$(DEV_DB_NAME) \
		-v $(CURDIR):/app -w /app webhost ./vendor/bin/phinx rollback -c phinx.php $(if $(MIGRATION_TARGET),-t $(MIGRATION_TARGET))
	$(MAKE) docker-dump-schema

# Run Phinx seeders against the docker mysql. Pass SEEDER=<Name> to run just one,
# e.g. `make docker-db-seed SEEDER=MemberSeeder`. With no SEEDER, all seeders run.
# Mounts the parent directory so seeders can read fixtures from the sibling
# openaustralia-parser checkout (used by PostcodeLookupSeeder).
docker-db-seed: vendor/autoload.php
	docker compose up -d mysql
	docker compose run --rm \
		-e DB_HOST=mysql -e DB_USER=$(TEST_DB_USER) -e DB_PASSWORD=$(TEST_DB_PASSWORD) -e DB_NAME=$(DEV_DB_NAME) \
		-v $(CURDIR)/..:/work -v $(CURDIR):/app -w /app webhost \
		./vendor/bin/phinx seed:run -c phinx.php $(if $(SEEDER),-s $(SEEDER))

# Dump the current docker mysql schema (no data) to db/schema.sql.
docker-dump-schema:
	docker compose up -d mysql
	docker compose exec -T mysql mysqldump \
		--user=$(TEST_DB_USER) --password=$(TEST_DB_PASSWORD) \
		--no-data --skip-comments --skip-add-drop-table --skip-set-charset \
		--routines --triggers --events --no-tablespaces \
		--ignore-table=$(DEV_DB_NAME).phinxlog \
		$(DEV_DB_NAME) > db/schema.sql
	@echo "Wrote db/schema.sql"

# Create the test database in the running Docker MySQL container.
# Only needed for existing containers where db/test-db-init.sql was not yet
# present at first-start time.  New containers pick it up automatically via
# the /docker-entrypoint-initdb.d/ mount in docker-compose.yml.
docker-test-db-create:
	docker compose up -d mysql
	docker compose exec mysql mysql -uroot -pexamplepassword \
		-e "CREATE DATABASE IF NOT EXISTS test_twfy CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci; GRANT ALL ON test_twfy.* TO 'twfyuser'@'%'; FLUSH PRIVILEGES;"
	@echo "test_twfy database ready"
xapian-index-docker:
	docker compose up -d
	docker compose run --rm \
		-v $(CURDIR)/..:/work \
		webhost bash -lc "mkdir -p /app/shared/search && cd /work/twfy/scripts && ../search/index.pl $(XAPIANDB) sincefile $(XAPIANDB_LASTUPDATED)"

lint: lint-php lint-perl

lint-php-ci lint-php:
	find -L www scripts -iregex '.*\.php$$' -print0 | xargs -0 -n 1 -P 4 php -l

lint-perl-ci lint-perl:
	find -L www scripts -iregex '.*\.pl$$' ! -path '*/archived/*' -print0 | xargs -0 -n 1 perl -c

phpcs:
	./vendor/bin/phpcs --standard=phpcs.xml --tab-width=4 --report=summary www scripts $(PHPCS_ARGS)

phpcs-ci phpcs-verbose:
	./vendor/bin/phpcs --standard=phpcs.xml --tab-width=4 www scripts $(PHPCS_ARGS)

phpcs-sonar:
	mkdir -p coverage
	./vendor/bin/phpcs --standard=phpcs.xml --tab-width=4 --report=checkstyle --report-file=coverage/phpcs.xml www scripts $(PHPCS_ARGS) || true

sonar-ci: test-coverage phpcs-sonar
	@command -v $(SONAR_SCANNER) >/dev/null 2>&1 || { echo "$(SONAR_SCANNER) not found on PATH"; exit 1; }
	$(SONAR_SCANNER)

# Install PHP (Composer) dependencies into ./vendor. Required before running
# migrations, tests, lint, etc. Re-run after pulling changes to composer.json/lock.
dependencies vendor/autoload.php:
	composer install --no-interaction --prefer-dist

install: dependencies scripts/run-with-lockfile

# Gleaned from infrastructure: roles/internal/openaustralia/tasks/main.yml
setup:
	sudo apt update
	sudo apt install \
	libmysqlclient-dev libssl-dev ghostscript imagemagick libdbd-mysql-perl libdbi-perl libmagickcore-dev \
	libmagickwand-dev libmysqlclient-dev libsearch-xapian-perl libxapian-dev libxml-rss-perl libxml-twig-perl \
	libxslt1-dev mysql-client libxml-simple-perl

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
	DB_HOST=$(TEST_DB_HOST) DB_USER=$(TEST_DB_USER) DB_PASSWORD=$(TEST_DB_PASSWORD) DB_NAME=$(TEST_DB_NAME) XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text --coverage-clover=coverage/clover.xml --coverage-html=coverage/html && \
	echo && echo "Open coverage/html/index.html to see the detailed coverage report"

test-coverage-docker:
	docker compose up -d
	docker compose run --rm -e DB_HOST=mysql -e DB_USER=$(TEST_DB_USER) -e DB_PASSWORD=$(TEST_DB_PASSWORD) -e DB_NAME=$(TEST_DB_NAME) -e XDEBUG_MODE=coverage -v $(CURDIR):/app -w /app webhost bash -lc "php -m | grep -qi xdebug || { echo 'xdebug is missing in twfy-app. Run make docker-build first.'; exit 1; }; ./vendor/bin/phpunit --coverage-text --coverage-clover=coverage/clover.xml --coverage-html=coverage/html $(TEST_ARGS)"


scripts/run-with-lockfile: scripts/run-with-lockfile.c
	gcc -o scripts/run-with-lockfile scripts/run-with-lockfile.c

check-links:
	ruby scripts/check-links.rb

