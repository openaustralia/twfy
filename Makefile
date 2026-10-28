# PLATFORM=linux/arm64,linux/amd64
PLATFORM=linux/amd64

TEST_DB_HOST ?= 127.0.0.1
TEST_DB_USER ?= twfyuser
TEST_DB_PASSWORD ?= twfypass
TEST_DB_NAME ?= test_twfy
DEV_DB_NAME ?= twfy
SONAR_SCANNER ?= sonar-scanner
XAPIANDB ?= /app/shared/search/searchdb
XAPIANDB_LASTUPDATED ?= $(XAPIANDB)/../searchdb-lastupdated
PERLLIB_PATH ?= ../perllib
PARSER_PATH ?= ../openaustralia-parser

.PHONY: help up services-up down clean clobber status logs shell docker-build xapian-index-docker lint lint-perl lint-perl-ci lint-perl-critic lint-php lint-php-ci phpcs phpcs-ci phpcs-verbose phpcs-sonar sonar-ci dependencies tailwind-build tailwind-watch playwright-update install setup test test-all install-xdebug test-coverage test-coverage-docker docker-test-db-create docker-test-db-migrate docker-db-shell docker-test-db-shell

help:
	@echo "Available targets:"
	@echo "  up                                  Start everything, including the site at http://localhost, once it's ready"
	@echo "  services-up                         Start MySQL only, once ready (for host-side tools)"
	@echo "  down                                Stop and remove the containers, keeping the database"
	@echo "  clean                               Remove containers, built images (VS Code's too) and dependencies, keeping the data"
	@echo "  clobber                             Also remove the database and index, then prune Docker machine-wide"
	@echo "  status                              Show each container's health and the host ports it's published on"
	@echo "  logs [SERVICE=<name>]               Follow the container logs"
	@echo "  shell                               Open a shell in the webhost container (a fresh one if it has stopped)"
	@echo "  docker-build                        Rebuild the Docker image from scratch (no cache)"
	@echo "  docker-db-migrate                   Run pending Phinx migrations against the docker mysql"
	@echo "  docker-db-migrate-down [MIGRATION_TARGET=<version>]  Roll back last migration (or to version)"
	@echo "  docker-db-seed [SEEDER=<Name>]      Run Phinx seeders against the docker mysql (all, or a specific one)"
	@echo "  docker-dump-schema                  Dump docker mysql schema (no data) to db/schema.sql"
	@echo "  docker-test-db-migrate              Run pending Phinx migrations against the test DB"
	@echo "  docker-db-shell                     Open interactive MySQL shell for the dev DB"
	@echo "  docker-test-db-shell                Open interactive MySQL shell for the test DB"
	@echo "  xapian-index-docker                Run Xapian indexing in Docker"
	@echo "  help                                Output this help"
	@echo "  lint                                Run lint-php and lint-perl on the www and scripts directories"
	@echo "  dependencies                        Install PHP (Composer) dependencies into ./vendor"
	@echo "  tailwind-build                      Install npm dependencies and build www/docs/style/tailwind/tailwind.css"
	@echo "  tailwind-watch                      Rebuild tailwind.css on every change while developing"
	@echo "  playwright-update                   Regenerate committed homepage screenshots"
	@echo "  install                             Install Composer/npm dependencies, build tailwind.css, and build script dependencies"
	@echo "  setup                               Install ubuntu packages required for development"
	@echo "  test [TEST_ARGS=...]                Run PHPUnit tests"
	@echo "  test-all [TEST_ARGS=...]            Run all PHPUnit tests including DB integration"
	@echo "  test-coverage [TEST_ARGS=...]       Run all tests with code coverage reports"
	@echo "  test-coverage-docker [TEST_ARGS=...] Run all tests with coverage in Docker (simplest method)"
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
	@echo "  TWFY_HTTP_PORT, TWFY_MYSQL_PORT, PHPLIB_PATH, PERLLIB_PATH, PARSER_PATH - see \"Port clashes\" in AGENTS.md"
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
		-f .devcontainer/Dockerfile \
		.

# The stack is defined in .devcontainer/compose.yaml (see "Docker development environment" in AGENTS.md).
# --wait returns once every service passes its healthcheck, so the next command can use them straight away.
up:
	docker compose up --build --wait -d

services-up:
	docker compose up --wait -d mysql

down:
	docker compose down --remove-orphans

# Everything this checkout built, so the next `make up` rebuilds from scratch. The database and index are kept.
# VS Code's dev container builds its own images on top of compose's. Only this project's containers say which
# they are, so they're noted before `down` removes them. Dependencies are bind mounts, partly owned by container
# users, so they're removed from inside a container rather than needing sudo.
clean:
	p=$$(docker compose config | sed -n 's/^name: //p'); \
	vsc=$$(docker ps -a --filter "label=com.docker.compose.project=$$p" --format '{{.Image}}' | grep '^vsc-' | sort -u); \
	docker compose down --remove-orphans && \
	{ [ -z "$$vsc" ] || docker rmi --force $$vsc; }
	-docker image rm twfy-app
	docker run --rm -v "$(CURDIR)":/work --entrypoint sh mysql:8.4.9 -c 'cd /work && rm -rf vendor node_modules www/docs/style/tailwind/tailwind.css'

# Reaches past this checkout, for after switching branches or changing dependencies. Other projects lose only
# download and build time, as volumes (where their databases live) are never pruned.
clobber: clean
	docker run --rm -v "$(CURDIR)":/work --entrypoint sh mysql:8.4.9 -c 'cd /work && rm -rf _mysqldata/* _mysqldata/.[!.]* _xapiandb/* shared'
	@echo "Pruning stopped containers, unused images and build cache from ALL projects (volumes are kept)"
	docker system prune --all --force

status:
	@docker compose ps -a --format 'table {{.Service}}\t{{.Status}}\t{{.Ports}}'

logs:
	docker compose logs -f $(SERVICE)

shell:
	@if [ -n "$$(docker compose ps -q --status running webhost)" ]; then docker compose exec webhost bash; else docker compose run --rm webhost bash; fi

# Run pending Phinx migrations against the docker mysql service via the webhost container.
# Always dumps the resulting schema to db/schema.sql so it can be committed alongside the migration.
docker-db-migrate: vendor/autoload.php
	docker compose up --wait -d mysql
	docker compose run --rm \
		-e DB_HOST=mysql -e DB_USER=$(TEST_DB_USER) -e DB_PASSWORD=$(TEST_DB_PASSWORD) -e DB_NAME=$(DEV_DB_NAME) \
		-v $(CURDIR):/app -w /app webhost ./vendor/bin/phinx migrate -c phinx.php
	$(MAKE) docker-dump-schema

# Roll back the last Phinx migration (or pass MIGRATION_TARGET=<version> to roll back to a specific version).
# Always dumps the resulting schema to db/schema.sql.
docker-db-migrate-down: vendor/autoload.php
	docker compose up --wait -d mysql
	docker compose run --rm \
		-e DB_HOST=mysql -e DB_USER=$(TEST_DB_USER) -e DB_PASSWORD=$(TEST_DB_PASSWORD) -e DB_NAME=$(DEV_DB_NAME) \
		-v $(CURDIR):/app -w /app webhost ./vendor/bin/phinx rollback -c phinx.php $(if $(MIGRATION_TARGET),-t $(MIGRATION_TARGET))
	$(MAKE) docker-dump-schema

# Run Phinx seeders against the docker mysql. Pass SEEDER=<Name> to run just one,
# e.g. `make docker-db-seed SEEDER=MemberSeeder`. With no SEEDER, all seeders run.
# Mounts the sibling openaustralia-parser checkout (PARSER_PATH) at /work so seeders can read its fixtures
# (used by PostcodeLookupSeeder).
docker-db-seed: vendor/autoload.php
	docker compose up --wait -d mysql
	docker compose run --rm \
		-e DB_HOST=mysql -e DB_USER=$(TEST_DB_USER) -e DB_PASSWORD=$(TEST_DB_PASSWORD) -e DB_NAME=$(DEV_DB_NAME) \
		-v $(abspath $(PARSER_PATH)):/work/openaustralia-parser:ro -v $(CURDIR):/app -w /app webhost \
		./vendor/bin/phinx seed:run -c phinx.php $(if $(SEEDER),-s $(SEEDER))

# Dump the current docker mysql schema (no data) to db/schema.sql.
docker-dump-schema:
	docker compose up --wait -d mysql
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
# the /docker-entrypoint-initdb.d/ mount in .devcontainer/compose.yaml.
docker-test-db-create:
	docker compose up --wait -d mysql
	docker compose exec mysql mysql -uroot -pexamplepassword \
		-e "CREATE DATABASE IF NOT EXISTS test_twfy CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci; GRANT ALL ON test_twfy.* TO 'twfyuser'@'%'; FLUSH PRIVILEGES;"
	@echo "test_twfy database ready"

docker-test-db-migrate:
	DB_HOST=$(TEST_DB_HOST) DB_USER=$(TEST_DB_USER) DB_PASSWORD=$(TEST_DB_PASSWORD) DB_NAME=$(TEST_DB_NAME) ./vendor/bin/phinx migrate -c phinx.php

docker-db-shell:
	docker compose up --wait -d mysql
	docker compose exec mysql mysql -u$(TEST_DB_USER) -p$(TEST_DB_PASSWORD) -h mysql $(DEV_DB_NAME)

docker-test-db-shell:
	docker compose up --wait -d mysql
	docker compose exec mysql env MYSQL_PWD=$(TEST_DB_PASSWORD) mysql -u$(TEST_DB_USER) -h mysql $(TEST_DB_NAME)

# index.pl reads ../conf/general and ../../perllib, so running it from /search gives it the docker config and
# perllib without touching the host's conf/general. The index is handed back to whoever owns the checkout, as the
# container runs as root.
xapian-index-docker:
	docker compose up --wait -d
	docker compose run --rm \
		-v $(CURDIR)/search:/search:ro \
		-v $(CURDIR)/conf/general-docker:/conf/general:ro \
		-v $(abspath $(PERLLIB_PATH)):/perllib:ro \
		webhost bash -lc "/search/index.pl $(XAPIANDB) sincefile $(XAPIANDB_LASTUPDATED) && chown -R $(shell id -u):$(shell id -g) /app/shared/search"

lint: lint-php lint-perl

lint-php-ci lint-php:
	find -L www scripts -iregex '.*\.php$$' -print0 | xargs -0 -n 1 -P 4 php -l

lint-perl-ci lint-perl:
	find -L www scripts search -iregex '.*\.pl$$' ! -path '*/archived/*' -print0 | xargs -0 -n 1 perl -c

lint-perl-critic:
	# \( \| \): find's default regex mode treats plain parentheses and pipe
	# as literal characters, not grouping or alternation (see
	# openaustralia/openaustralia#936) - escaping them is what makes this
	# match .pl/.pm files at all, rather than silently matching nothing.
	# -r (--no-run-if-empty) is a second, independent safety net: it stops
	# xargs running perlcritic with no file argument if some future path
	# change ever makes -iregex match nothing again.
	find -L scripts search -iregex '.*\.\(pl\|pm\)$$' ! -path '*/archived/*' -print0 | xargs -0 -r -n 1 perlcritic --profile .perlcriticrc

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

# Install npm (Tailwind) dependencies into ./node_modules.
node_modules: package.json
	npm install

# Rebuild www/docs/style/tailwind/tailwind.css from the Tailwind classes used in www/**/*.php.
# Re-run after adding new Tailwind classes to templates.
tailwind-build: node_modules
	npm run build:css

tailwind-watch: node_modules
	npm run watch:css

playwright-update: node_modules
	npx playwright test --update-snapshots

install: dependencies tailwind-build scripts/run-with-lockfile

# Gleaned from infrastructure: roles/internal/openaustralia/tasks/main.yml
setup:
	sudo apt update
	sudo apt install \
	libmysqlclient-dev libssl-dev ghostscript imagemagick libdbd-mysql-perl libdbi-perl libmagickcore-dev \
	libmagickwand-dev libmysqlclient-dev libsearch-xapian-perl libxapian-dev libxml-rss-perl libxml-twig-perl \
	libxslt1-dev mysql-client libxml-simple-perl libperl-critic-perl \
	build-essential autoconf bison re2c pkg-config libicu-dev libonig-dev libzip-dev libpq-dev \
	libreadline-dev libgd-dev libcurl4-openssl-dev libsqlite3-dev libxml2-dev libjpeg-dev libpng-dev

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

# Migrates the test database first, as a fresh one is empty. The report is handed back to whoever owns the checkout,
# as the container runs as root.
test-coverage-docker:
	docker compose up --wait -d
	docker compose run --rm -e DB_HOST=mysql -e DB_USER=$(TEST_DB_USER) -e DB_PASSWORD=$(TEST_DB_PASSWORD) -e DB_NAME=$(TEST_DB_NAME) -e XDEBUG_MODE=coverage -v $(CURDIR):/app -w /app webhost bash -lc "php -m | grep -qi xdebug || { echo 'xdebug is missing in twfy-app. Run make docker-build first.'; exit 1; }; ./vendor/bin/phinx migrate -c phinx.php || exit 1; ./vendor/bin/phpunit --coverage-text --coverage-clover=coverage/clover.xml --coverage-html=coverage/html $(TEST_ARGS); status=\$$?; chown -R $(shell id -u):$(shell id -g) coverage; exit \$$status"


scripts/run-with-lockfile: scripts/run-with-lockfile.c
	gcc -o scripts/run-with-lockfile scripts/run-with-lockfile.c

check-links:
	ruby scripts/check-links.rb

