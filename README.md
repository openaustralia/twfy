# They Work For You (aussie rules)

This is a fork of a 2001-ish era PHP app from MySociety in the UK, repurposed for Australia. This is the software running on https://openaustralia.org.au

## What is OpenAustralia.org.au ?

OpenAustralia.org.au is a website run by the non-partisan charity, OpenAustralia Foundation, which makes Australian government and parliamentary information easily accessible to the public through tools such as searching Hansard (parliamentary debates) and tracking politicians' voting records. The site aims to increase transparency and civic engagement in Australian democracy. It provides platforms to easily follow what MPs and Senators say and do, and tracks their registers of interests.

## What is this data?

Everything elected politicians say in Australia's Senate and Parliament is recorded in Australia's Official Hansard. This documentation is obtained using scrapers (see https://github.com/openaustralia/openaustralia-parser/ )
and displayed on openaustralia.org.au. 

## data feeds

Data is also provided for public use at http://data.openaustralia.org.au/

TheyVoteForYou.org.au is one of the users of this data. 

## Development


### Installing php

We use [mise](https://mise.jdx.dev/) to manage the PHP toolchain. The
required version is pinned in [`.php-version`](.php-version) (which mise
auto-detects). Once mise is installed, run:

```bash
mise install
```

from the repo root, which will install the version of PHP this project
expects.

If `mise install` fails while compiling PHP from source, you may be
missing build dependencies.

On Ubuntu:

```bash
sudo apt update
sudo apt install re2c bison autoconf build-essential libxml2-dev libssl-dev libcurl4-openssl-dev libpng-dev libjpeg-dev libonig-dev libzip-dev libgd-dev
```

On macOS (with [Homebrew](https://brew.sh/)):

```bash
brew install autoconf bison re2c pkg-config libxml2 openssl@3 curl libpng jpeg oniguruma libzip gd
```

### Bumping the PHP version

The PHP version is declared in a few places. To bump it, update all of
the following so they stay in sync:

1. [`.php-version`](.php-version) — the pinned version (e.g. `8.4`). This
   is read by both `mise` (for local installs) and GitHub Actions
   (via `shivammathur/setup-php`'s `php-version-file`).
2. [`composer.json`](composer.json) — `require.php` (the supported range,
   e.g. `^8.4.0`) and `config.platform.php` (the exact patch version
   Composer resolves dependencies against, e.g. `8.4.1`).
3. [`Dockerfile`](Dockerfile) — the `ARG PHP_VERSION=8.3` default near the
   top. This controls the Ubuntu `php<version>-*` package names. You can
   also override it at build time with
   `docker build --build-arg PHP_VERSION=8.4 .`.

After bumping, run `composer update` to refresh `composer.lock`, rebuild
the Docker image (`make docker`), and run the test suite.

### Installing composer managed and script dependencies

PHP (Composer) dependencies are installed into `./vendor`. Most other
targets (tests, migrations, lint) depend on this having been done:

```bash
make dependencies      # composer install only
# or
make install           # composer install + compile scripts/run-with-lockfile
```

Re-run `make dependencies` after pulling changes that touch `composer.json`
or `composer.lock`.

### Running the checks CI does

```bash
make lint-php-ci | grep -v "No syntax errors detected in" # ignore all the "its ok" messages
make phpcs-ci
composer validate
```

### Updating formatting

Use `phpcbf` to fix formatting that GitHub Actions complains about, eg:

```bash
./vendor/bin/phpcbf www/includes/easyparliament/alert.php www/includes/easyparliament/user.php
```

## Running the app locally

The easiest way to run the whole stack (Apache + PHP + MySQL) is via Docker:

```bash
make docker          # build the image then start the containers
# or, if the image is already built:
make docker-run
```

The site will be available at <http://localhost> (override the port with
`TWFY_HTTP_PORT=8080 make docker-run`), and MySQL on `127.0.0.1:3306`
(override with `TWFY_MYSQL_PORT`).

On first start, load the schema and apply any migrations:

```bash
make docker-db-migrate              # apply pending Phinx migrations
```

To populate the Xapian search index (required for the search box to return
results), run:

```bash
make xapian-index-docker         # incremental index using the timestamp file
```

Stop everything with `docker compose down`.

## Database migrations

We use [Phinx](https://book.cakephp.org/phinx/0/en/index.html) to manage
schema changes. Migration files live in `db/migrations/` and the canonical
schema is checked in at `db/schema.sql`.

### Adding a new migration

Create a new migration file (timestamped) and edit it:

```bash
docker compose run --rm -v $(pwd):/app -w /app webhost \
    ./vendor/bin/phinx create AddSomethingDescriptive -c phinx.php
```

Implement `change()` (or `up()`/`down()`) in the generated file under
`db/migrations/`.

### Running migrations

```bash
make docker-db-migrate                       # apply all pending migrations
make docker-db-migrate-down                  # roll back the most recent migration
make docker-db-migrate-down MIGRATION_TARGET=20260530000000   # roll back to a specific version
```

### Updating the checked-in schema

After adding (or rolling back) a migration, dump the resulting schema and
commit it alongside the migration file so reviewers can see the net effect
and fresh checkouts don't need to replay history:

```bash
make docker-dump-schema          # writes db/schema.sql
git add db/migrations/<your_new_migration>.php db/schema.sql
```

## Testing

### Running the tests

`make test` runs the whole phpunit suite. Integration tests skip
themselves automatically if no database is reachable, so you can run
this without MySQL:

```bash
make test
```

To include the integration tests, start MySQL first:

```bash
make docker-run
```

Wait a few seconds for MySQL to be ready, then run:

```bash
make test-all
```

### Running tests with coverage

To generate code coverage reports (requires Xdebug or PCOV):

```bash
make docker-run
make test-coverage
```

Or run coverage inside Docker (no host PHP extensions needed):

```bash
make test-coverage-docker
```

Coverage reports are generated in `coverage/clover.xml` (for SonarCloud) and `coverage/html/` (browsable HTML).

### Database credentials when using docker

When running tests with the database:
- Host: `127.0.0.1` (or `mysql` inside Docker)
- User: `twfyuser`
- Password: `twfypass`
- Database: `twfy`

These are configured in `phpunit.xml` via environment variables (`DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`).

### Test organization

Tests are split into two types:

1. **Unit tests** (no database required): Tests logic, parsing, and conditional logic. Located in `tests/*Test.php`.
2. **Integration tests** (database required): Tests database queries and interactions. Located in `tests/*IntegrationTest.php`.

Integration tests automatically skip if the database is not available, so the test suite will still pass in CI environments.

### Stopping Docker

To stop the Docker containers:

```bash
docker compose down
```

### Sharing database with openaustralia-parser for development

You can setup local development for both repos by:

```bash
# DO NOT DO THIS ON PRODUCTION!!!!
cd ../twfy
cp conf/general-example.local-dev conf/general
make docker-db-migrate                # or: ./vendor/bin/phinx migrate -c phinx.php

cd ../openaustralia-parser
bundle exec rake db:fixtures:load   # for a limited set of fixtures
bundle exec rake db:stats # to show which tables have data
```

### Checking Links

To perform a simplistic static check of links in php files, run:

```bash
make check-links
```

This will output suggested sed commands to run to fix urls in php files.
NOTE: It isn't smart enough to handle dynamically generated urls, so check before applying recommendations.

It follows permanent redirections and suggests the url be updated accordingly, for example (note the http to https
change and the slash added to the end of the url):

```
sed -i 's|http://theyworkforyou.com/api|https://www.theyworkforyou.com/api/|g' www/docs/api/index.php
```

It will report broken links (check it is not part of a dynamic link that does work when checked in full):

```
# BROKEN http://hurring.com/code/python/serialize/ (404) in files: www/docs/api/index.php
# BROKEN https://www.aph.gov.au/Help/Disclaimer_Privacy_Copyright#c (403) in files: www/docs/api/index.php
```

It will list temporary redirections, which you probably should leave as is, aside from changing http to https where
appropriate:

```
# Ignore 302 redirect http://creativecommons.org/licenses/by-nc-nd/3.0/au/ to https://creativecommons.org/licenses/by-nc-nd/3.0/au/ in files: www/docs/api/index.php
# Ignore 302 redirect http://creativecommons.org/licenses/by-sa/2.5/ to https://creativecommons.org/licenses/by-sa/2.5/ in files: www/docs/api/index.php
```
