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
Composer: `../phplib` (a handful of includes: auth, rabx, tracking), `../perllib` (`use lib` in
`search/index.pl` and the `scripts/*.pl` cron scripts), `../openaustralia-parser` (cron scripts and the postcode
seeder), `../shlib` (shellcheck CI). Docker mounts `../phplib`; CI checks siblings out and symlinks them, matching
your branch name in the sibling repo if one exists.

## Laravel

Laravel 13 lives alongside the legacy pages (`docs/adr/0002-laravel-auth-replaces-homegrown-sign-in.md`,
openaustralia/openaustralia#1069): `bootstrap/app.php`, `config/`, `lang/`, `routes/` and `storage/` at the repo root,
with `www/docs` as its public folder. `init.php` boots it through `twfy_laravel()` (`www/includes/laravel.php`), which
skips Laravel's error handling and `.env` loading, turns off facade aliases, and hands Laravel the Eloquent
connection `eloquent.php` made, so both share one connection. Settings come from `conf/general` (`APP_KEY` among
them), not `.env`. Composer runs `artisan package:discover` after installing, which writes `bootstrap/cache/`.

Sign-in and password reset are Laravel's (Fortify). Apache sends only `/user/login` and `/user/password...` to Laravel,
through `www/docs/laravel.php`, which loads the legacy bootstrap first so `LoginPage`, `PasswordPages` and `ErrorPage`
(in `www/includes/Auth/`) can draw their pages with the legacy `$PAGE`. Laravel's own error pages are Blade views and
need a writable compiled-views folder, so `bootstrap/app.php` draws errors with `ErrorPage` instead. A reset link is
emailed through the legacy `send_template_email()` (template `password_reset`), not Laravel's mail, so it keeps the
site's headers and Postal tags; `User::sendPasswordResetNotification()` does that. The form that asks for a link gives
the same answer for every address, and a reset signs the user out everywhere by deleting their `sessions` rows.
`Auth\Authenticator` checks the password, including the old md5-crypt (`$1$`) hashes, which it upgrades to bcrypt.
`THEUSER` reads who is signed in from Laravel's session or "remember me" cookie (`twfy_laravel_user()`), and
`twfy_laravel_with_session()` lets a legacy page sign someone in or out, and keeps flash data for Laravel's own request. Sessions and login throttling live in the
database, as Apache can't write into a release directory. The legacy `epuser_id` cookie is no longer read. Logout is
still the legacy GET page, and the login form still has no CSRF token; both change when CSRF reaches every form.
The Docker `webhost` bind-mounts `bootstrap/`, `config/`, `lang/`, `routes/` and `storage/` beside `www/`: after a
branch switch that deletes and recreates one, recreate the container (`docker compose up -d --force-recreate webhost`).

Admin pages are moving to Blade views and controller classes. A page file in `www/docs/admin/` only calls a controller
in `www/includes/Admin/` (extending `AdminController`), which fetches the data and returns a Blade view from
`www/resources/views/admin/`, drawn inside the legacy `$PAGE` layout. Blade's `{{ }}` escapes every value, so views
never build HTML from data; shared table parts are anonymous components in `www/resources/views/components/admin/`.
`config/view.php` compiles views into `storage/framework/views`. Apache can't write into a release, so Capistrano links
that folder to `shared/` (`linked_dirs` in the umbrella repo), which the infrastructure repo creates writable for `www-data`.
Pages not yet converted are `users.php`, `proof*.php`, `usage.php` and the two glossary pages.

## Database access

The ORM is Eloquent, from `laravel/framework` ^13. Each table has a model in `www/includes/Models/`, namespaced
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
make services-up  # MySQL and Elasticsearch only, for host-side tools
make status       # each container's health and host ports
make logs         # follow the logs (SERVICE=<name> for one container)
make shell        # a shell in the app container, or a fresh one if it has stopped
make down         # stop and remove the containers, keeping the data
make clean        # also remove built images and dependencies, so the next start rebuilds; keeps the data
make clobber      # also remove the data, and prune unused Docker images and cache machine-wide
```

`make up` and `make services-up` return once every service passes its healthcheck. Run `make` for the full list.
A start that takes longer than `START_LIMIT` (in `.devcontainer/compose.yaml`) counts as failed and is reported loudly on purpose;
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
| `TWFY_ELASTICSEARCH_PORT` | 9201    | Elasticsearch, on 127.0.0.1. Not 9200, which They Vote for You's stack uses. `make test-all` passes it on to the specs as `ELASTICSEARCH_TEST_URL` |
| `TWFY_MAILPIT_PORT` | 8025          | Mailpit's web interface, where the site's outgoing mail is caught       |
| `PHPLIB_PATH`     | `../../phplib`  | the sibling phplib checkout. Relative to `.devcontainer/`, so an absolute path is simplest in a worktree |
| `PERLLIB_PATH`    | `../perllib`    | the sibling perllib checkout, for `make xapian-index-docker`. Relative to the repo root |
| `PARSER_PATH`     | `../openaustralia-parser` | the sibling parser checkout, for `make docker-db-seed`. Relative to the repo root |

Put overrides in `.env` at the repo root, which is gitignored, with different values in each worktree. Compose reads
it however you start the stack. Containers and data are named `twfy` (`name:` in `compose.yaml`), so a second checkout of
this repo, such as a worktree, shares them unless its `.env` also sets its own `COMPOSE_PROJECT_NAME`. For
host-side commands to see the same values, put `dotenv` in a gitignored `.envrc` if you use direnv, or
run `set -a; . ./.env; set +a`. Variables already set in your shell win over `.env`, and a worktree inside the main
checkout (such as under `.claude/worktrees/`) picks up the main checkout's `.envrc`, so give each worktree its own
`.envrc`. Services you rarely need from the host get a random port, which `make status` shows.
To see what holds a port, use `ss -ltnp | grep 3306` (Linux) or `lsof -iTCP:3306 -sTCP:LISTEN` (macOS); a database
server installed on the host is the usual culprit.

## Commands

```
make help                 # target list
make lint                 # php -l and perl -c sweeps
make phpcs                # phpcs (Drupal standard, phpcs.xml) over www and scripts
./vendor/bin/phpcbf <files>
make test                 # pest unit tests (*Test.php)
make test-all             # pest including *IntegrationTest.php - needs the docker MySQL up
make docker-test            # all of pest in the webhost container, against test_twfy (not the dev database)
make test-coverage-docker # tests with coverage inside docker
make docker-dump-schema   # regenerate db/schema.sql after migrating
```

CI (`.github/workflows/php.yml`) runs lint + phpcs + `composer audit`, then the `PHPUnit` and `Pest` suites as separate
jobs against MySQL 8.4. A **coverage** job then merges their coverage (`phpcov`) and JUnit reports
(`.github/scripts/merge-junit.php`) and sends the result to SonarCloud, which reads one JUnit file. A **schema-check** job
migrates a fresh database and diffs the dump against `db/schema.sql`.
`perl.yml` and `shellcheck.yml` lint the Perl and cron-shell scripts against the sibling checkouts.

## Gotchas

- **Lint after every edit.** Run `php -l` and `./vendor/bin/phpcs --standard=phpcs.xml` on each PHP file you change,
  straight after changing it, and fix what they report before moving on. Don't leave it to CI.
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
- Tests are mid-move from PHPUnit classes to Pest specs (openaustralia/openaustralia#1049). **Write every new test as a
  Pest spec, never a new PHPUnit class.** Specs go in `tests/Pest/`, in Laravel's layout for new work: `Feature/`
  for controllers and Blade views (`ViewTestCase` gives `$this->view('admin.alerts', $data)->assertSee()`), `Support/`
  for helpers
  and follow [betterspecs.org](https://betterspecs.org): `describe()` per function, `when`/`with`/`without` contexts,
  descriptions under 40 characters, one `expect()` per `it()`, datasets for repeated cases. Everything else under
  `tests/` is a PHPUnit class. Run everything with `./vendor/bin/pest`; a bare `phpunit` refuses to load the specs,
  but `phpunit --testsuite=PHPUnit` works and CI uses it, as Pest rewrites the JUnit file paths Sonar needs.
- Keep one test class per file. With several, PHPUnit and Pest each run a different one and skip the rest silently.
- Tests: `*Test.php` are unit tests, `*IntegrationTest.php` need the database; `tests/bootstrap.php` wraps each
  test in mysqli and Eloquent transactions rolled back in `tearDown`, and defines the constants the legacy code
  expects. Seeders are idempotent and `SEEDER=<Name>` runs one.
- `tests/Pest/UsageStatsElasticsearchIntegrationTest.php` sends rows to a real Elasticsearch 7.17 at
  `ELASTICSEARCH_TEST_URL`, which `make test-all` points at the stack's own and CI at a service container. Without it
  the specs skip locally and fail when `CI` is set. Each test writes to its own `oa-*-spec<random>-*` indices and
  deletes them afterwards. `UsageStatsExporterIntegrationTest.php` covers the same code against a fake.
- `tests/Pest/Xml2dbIntegrationTest.php` runs every spec against both loaders, `scripts/xml2db.pl` (Perl) and
  `scripts/importxml.php` (PHP, classes in `www/includes/XmlImport/`), as subprocesses with invented XML. A final spec
  loads each scenario with both and requires the same rows. The loaders commit on their own connection, so the specs
  cannot use the transactional case: they empty the tables the loaders write before and after each test, and only
  run when `DB_NAME` contains "test". The PHP loader's coverage comes from `tests/Pest/Support/subprocess-coverage.php`,
  which `Xml2dbRunner` prepends when `SUBPROCESS_COVERAGE_DIR` is set (CI sets it); it writes `.cov` files that
  `phpcov merge` combines with the parent's. Perl needs `perllib` beside the repo and the modules `DBD::mysql`, `XML::Twig`
  and `HTML::Entities`; without them the specs skip locally and fail when `CI` is set.
- `scripts/xml2db.pl` and `scripts/importxml.php` carry on after a day fails: each records the error in `import_errors`
  (source `loader`), loads the next day, and exits 1 at the end. A day that loads clears its own earlier loader errors.
  The Perl loader's `--recent` doesn't move a house's `xml2db-lastload` marker past a failed day in that house; the other house's marker still moves. The parser records its own
  errors (source `parser`) through `scripts/record-import-error.php` and `scripts/record-import-success.php`, and the
  admin proof report counts the days with errors. Each stage clears only its own rows.
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

Single-context: one `GLOSSARY.md` and one `docs/adr/` at the root, both created lazily. See `docs/agents/domain.md`.
