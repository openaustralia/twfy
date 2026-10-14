# AGENTS.md

This file provides guidance to AI coding agents (Claude Code, GitHub Copilot, and others) when working with code in
this repository. `CLAUDE.md` and `.github/copilot-instructions.md` point here so the guidance lives in one place.

## What this repository is

The PHP web application behind OpenAustralia.org.au - a fork of mySociety's 2001-era TheyWorkForYou, repurposed for
Australia. PHP 8.3 (`composer.json`, `mise.toml`), MySQL 8.4, Apache, Xapian search. Most of the app is legacy
include-style PHP under `www/includes/easyparliament/` with globals and constants, plus a newer namespaced corner
(`OpenAustralia\TWFY\` maps to `www/includes/`, Eloquent models in `www/includes/Models/`). Expect 2001-era idioms;
match the file you're in, don't modernise in passing.
The exception is types: new functions and methods should declare parameter and return type hints, even in a legacy
file. Don't retrofit existing untyped code unless you're already changing its signature.

It is consumed as a git submodule of [`openaustralia/openaustralia`](https://github.com/openaustralia/openaustralia)
(the umbrella repository), which owns deployment. Sibling repos are wired in by **relative filesystem path**, not
Composer: `../phplib` (a handful of includes: auth, rabx, mapit, tracking), `../perllib` (`use lib` in
`search/index.pl` and the `scripts/*.pl` cron scripts), `../openaustralia-parser` (cron scripts and the postcode
seeder), `../shlib` (shellcheck CI). Docker mounts `../phplib`; CI checks siblings out and symlinks them, matching
your branch name in the sibling repo if one exists.

## Database access

The ORM is Eloquent (`illuminate/database` ^11). Each table has a model in `www/includes/Models/`, namespaced
`OpenAustralia\TWFY\Models` (for example `Alert`, `Member`, `Hansard`, `EmailSuppression`). A model sets `$table` and
`$primaryKey`, lists `$fillable` columns, declares `$casts` for booleans and dates, and documents its columns with
`@property` tags. Copy `Models/Alert.php` for the shape.

- New code uses these models for database access. Don't add new `parlDBQuery()` or raw SQL calls.
- If a table has no model yet, add one in `Models/` rather than falling back to raw SQL.
- When you modify code that uses `parlDBQuery()` or other raw SQL, convert that query to the ORM in the same
  change, but as its own commit. This is how the legacy calls get removed over time.
- Keep raw SQL only where Eloquent can't express the query, and say why in the commit message.

## Docker development environment

One stack, defined in `.devcontainer/compose.yaml`, serves every way of working: `make`, plain `docker compose`
(through the root `compose.yaml`), and the dev container in VS Code, RubyMine, Codespaces or
`devcontainer up --workspace-folder .`. They all share the same containers and data.

```
make up           # everything, including the app at http://localhost, once it's ready
make services-up  # MySQL only, for host-side tools
make status       # each container's health and host ports
make logs         # follow the logs (SERVICE=<name> for one container)
make shell        # a shell in the app container, or a fresh one if it has stopped
make down         # stop and remove the containers, keeping the data
make clean        # also remove built images and dependencies, so the next start rebuilds; keeps the data
make clobber      # also remove the data, and prune unused Docker images and cache machine-wide
```

`make up` and `make services-up` return once every service passes its healthcheck. Run `make` for the full list.
A start that takes longer than `START_LIMIT` (in `.devcontainer/compose.yaml`) counts as failed, and stops loudly on purpose;
see "If the app won't start" in the README.

`conf/general` is gitignored, and only host-side tools need it (`cp conf/general-example.local-dev conf/general`):
the container reads `conf/general-docker`, and its `DB_*` environment variables win over both (`phinx.php`). On start,
the `webhost` container installs `vendor/` and builds `tailwind.css` if they're missing, then applies pending
migrations. Seed data and the search index are separate steps:

```
make docker-db-seed       # sample members, hansard, postcodes, ...
make xapian-index-docker  # build the Xapian search index
```

`_mysqldata/` and `_xapiandb/` hold the database and index on the host, so `make clobber` removes them from inside a
container, as some files are owned by container users.

### Port clashes

| Variable          | Default         | Publishes or points at                                                  |
|-------------------|-----------------|-------------------------------------------------------------------------|
| `TWFY_HTTP_PORT`  | 80              | the site                                                                |
| `TWFY_MYSQL_PORT` | 3306            | MySQL. `Makefile.dev` passes it on to host-side scripts as `DB_PORT`    |
| `PHPLIB_PATH`     | `../phplib`     | the sibling phplib checkout, which a worktree has to point at by hand  |

Put overrides in `.env` at the repo root, which is gitignored, with different values in each worktree. Compose reads
it however you start the stack. Containers and data are named `twfy` (`name:` in `compose.yaml`), so a second checkout of
this repo, such as a worktree, shares them unless its `.env` also sets its own `COMPOSE_PROJECT_NAME`. For
host-side commands to see the same values, put `dotenv` in a gitignored `.envrc` if you use direnv, or
run `set -a; . ./.env; set +a`. Services you rarely need from the host get a random port, which `make status` shows.
To see what holds a port, use `ss -ltnp | grep 3306` (Linux) or `lsof -iTCP:3306 -sTCP:LISTEN` (macOS); a database
server installed on the host is the usual culprit.

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

- **Lint after every edit.** Run `php -l` and `./vendor/bin/phpcs --standard=phpcs.xml` on each PHP file you change,
  straight after changing it, and fix what they report before moving on. Don't leave it to CI.
- **`make test-docker` doesn't exist** even though `README.md` and `make help` mention it; the real target is
  `make test-coverage-docker`.
- **Migrations are dual-tracked.** After any Phinx migration, `db/schema.sql` must be regenerated
  (`make docker-dump-schema`) and committed, or the schema-check CI job fails. `make docker-db-migrate` does this
  for you. Keep MySQL pinned at 8.4 everywhere - `mysqldump` output is version-sensitive and drift shows up as
  spurious schema diffs.
- **Three sets of DB credentials** exist (`conf/general-example.local-dev`, `.devcontainer/compose.yaml`,
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

## Agent skills

Configuration the engineering skills read. These files describe how this repo works; edit them directly rather
than re-running the setup skill.

### Issue tracker

Issues live as GitHub issues in the umbrella repo, `openaustralia/openaustralia` — issues are disabled on this
repo. Driven by the `gh` CLI with `-R openaustralia/openaustralia`. See `docs/agents/issue-tracker.md`.

### Triage labels

The default five-label vocabulary: `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`.
See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: one `CONTEXT.md` and one `docs/adr/` at the root, both created lazily. See `docs/agents/domain.md`.
