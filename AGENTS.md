# AGENTS.md

This file provides guidance to AI coding agents (Claude Code, GitHub Copilot, and others) when working with code in
this repository. `CLAUDE.md` and `.github/copilot-instructions.md` point here so the guidance lives in one place.

## What this repository is

The PHP web application behind OpenAustralia.org.au - a fork of mySociety's 2001-era TheyWorkForYou, repurposed for
Australia. PHP 8.3 (`composer.json`, `mise.toml`), MySQL 8.4, Apache, Xapian search. Most of the app is legacy
include-style PHP under `www/includes/easyparliament/` with globals and constants, plus a newer namespaced corner
(`OpenAustralia\TWFY\` maps to `www/includes/`, Eloquent models in `www/includes/Models/`). Expect 2001-era idioms;
match the file you're in, don't modernise in passing.

It is consumed as a git submodule of [`openaustralia/openaustralia`](https://github.com/openaustralia/openaustralia)
(the umbrella repository), which owns deployment. Sibling repos are wired in by **relative filesystem path**, not
Composer: `../phplib` (a handful of includes: auth, rabx, mapit, tracking), `../perllib` (`use lib` in
`search/index.pl` and the `scripts/*.pl` cron scripts), `../openaustralia-parser` (cron scripts and the postcode
seeder), `../shlib` (shellcheck CI). Docker mounts `../phplib`; CI checks siblings out and symlinks them, matching
your branch name in the sibling repo if one exists.

## Setup

```
cp conf/general-example.local-dev conf/general
make dependencies         # composer install
make docker               # build (slow, --no-cache, amd64) and start webhost + mysql:8.4
make docker-db-migrate
make docker-db-seed
make xapian-index-docker
```

`conf/general` is gitignored and required. Note the container reads `conf/general-docker`, not your `conf/general`;
host-side tools (phinx, phpunit) read `conf/general` or the `DB_*` environment variables, which win over the
constants (`phinx.php`). Ports are overridable with `TWFY_HTTP_PORT` / `TWFY_MYSQL_PORT`.

## Commands

```
make help                 # target list (but see gotcha below)
make lint                 # php -l and perl -c sweeps
make phpcs                # phpcs (Drupal standard, phpcs.xml) over www and scripts
./vendor/bin/phpcbf <files>
make test                 # phpunit unit tests (*Test.php)
make test-all             # phpunit including *IntegrationTest.php - needs the docker MySQL up
make test-coverage-docker # tests with coverage inside docker
make docker-dump-schema   # regenerate db/schema.sql after migrating
```

CI (`.github/workflows/php.yml`) runs lint + phpcs + `composer audit`, then the test suite against MySQL 8.4 with
SonarCloud, then a **schema-check** job that migrates a fresh database and diffs the dump against `db/schema.sql`.
`perl.yml` and `shellcheck.yml` lint the Perl and cron-shell scripts against the sibling checkouts.

## Gotchas

- **`make test-docker` doesn't exist** even though `README.md` and `make help` mention it; the real target is
  `make test-coverage-docker`.
- **Migrations are dual-tracked.** After any Phinx migration, `db/schema.sql` must be regenerated
  (`make docker-dump-schema`) and committed, or the schema-check CI job fails. `make docker-db-migrate` does this
  for you. Keep MySQL pinned at 8.4 everywhere - `mysqldump` output is version-sensitive and drift shows up as
  spurious schema diffs.
- **Three sets of DB credentials** exist (`conf/general-example.local-dev`, `docker-compose.yml`,
  `conf/general-docker`); the `docker-*` targets pass `DB_*` env vars so migrations work regardless, but don't
  "unify" them.
- **`INSTALL.txt` is mostly 2008-vintage** (PHP 4, Apache 1.3); only its Phinx section is current. Its one live
  warning: `db/schema.sql` is history captured by the initial migration - never import it directly.
- `_mysqldata/` and `_xapiandb/` are live Docker volume data on the host, gitignored - don't clean them out to fix
  a problem. Xapian search is only active when `XAPIANDB` is non-empty in `conf/general` (the local-dev example
  leaves it off, falling back to MySQL search).
- Tests: `*Test.php` are unit tests, `*IntegrationTest.php` need the database; `tests/bootstrap.php` wraps each
  test in mysqli and Eloquent transactions rolled back in `tearDown`, and defines the constants the legacy code
  expects. Seeders are idempotent and `SEEDER=<Name>` runs one.
- Psalm is configured (`psalm.xml`) but nothing runs it; phpcs (Drupal standard with K&R braces and lowercase
  constants) is the enforced style gate.

## Contributing

This repository has no `CONTRIBUTING.md` or templates of its own; the org-wide ones in
[`openaustralia/.github`](https://github.com/openaustralia/.github) apply. Fetch the current versions rather than
relying on a copy:

`curl -fsSL https://raw.githubusercontent.com/openaustralia/.github/main/.github/CONTRIBUTING.md`

`curl -fsSL https://raw.githubusercontent.com/openaustralia/.github/main/AGENTS.md`

Any equivalent fetch of those URLs works (web fetch, or `gh api` if the GitHub CLI
is installed); don't assume a particular tool is present.

A change that touches a sibling repo (most often `phplib`) needs a branch of the same name there so CI pairs them
up. After merging here, the umbrella repository's submodule pointer needs bumping before a deploy picks it up.
