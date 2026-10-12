# HTML Viewer – Agent Notes

Nextcloud app (`htmlviewer`) that shows and edits HTML files in the Viewer app and offers itself as a direct editor to the Android and desktop clients.
User-facing settings are documented in [README.md](README.md) (mirrored in `appinfo/info.xml`), changes are tracked in [CHANGELOG.md](CHANGELOG.md).

## Stack

- Nextcloud 35 only (`appinfo/info.xml`), PHP 8.3 – 8.5
- Backend: PHP in `lib/` (namespace `OCA\HtmlViewer`), routes in `appinfo/routes.php`, template in `templates/`
- Frontend: Vue 3 / Nextcloud Vue 9 in `src/`, built with webpack into `js/` (git-ignored)
- Translations: gettext files in `translations/`, compiled into `l10n/` (git-ignored) by `npm run l10n:build`

## Dev environment

`docker compose up -d` starts MariaDB, Nextcloud 35 (FPM) and nginx on https://localhost.
The `app` container is built from [docker/nextcloud/Dockerfile](docker/nextcloud/Dockerfile) (`nextcloud:35-fpm` + Composer).

- The project is mounted **read-only** at `/app` and symlinked to `custom_apps/htmlviewer` by the hook scripts in `docker/nextcloud/`.
  Keep it read-only: an app update in Nextcloud would delete the symlinked source tree through a writable mount.
- Run `occ` as `www-data`: `docker compose exec -u www-data app php occ <command>`
- TLS certificates for nginx: `npm run mkcert`

## Commands

| Task                  | Command                         |
|-----------------------|---------------------------------|
| Build frontend        | `npm run build` (`npm run watch` while developing) |
| Lint frontend         | `npm run lint`, `npm run stylelint` |
| Build translations    | `npm run l10n:build`            |
| PHP unit tests        | `make test`                     |

## PHP unit tests

- PHPUnit 13 against the public API from `nextcloud/ocp` (`dev-stable35`), no Nextcloud server is bootstrapped.
- **Run them with `make test`.** It installs the Composer dependencies in a one-off container (the `app` mount is read-only)
  and then runs PHPUnit inside the running `app` container:
  ```bash
  docker compose run --rm --no-deps -u "$(id -u):$(id -g)" -v "$PWD:/src" -w /src --entrypoint composer app install --no-interaction
  docker compose exec app php /app/vendor/bin/phpunit -c /app/tests/phpunit.xml
  ```
  Single test: append `--filter DirectEditingServiceTest` to the second command.
- Tests cannot run on the host: PHPUnit 13 needs PHP ≥ 8.4.1, and `composer.json` pins the platform to `8.4.1` for the dev dependencies while the app itself supports 8.3.
- Layout: `tests/Unit/<same path as lib/>/<Class>Test.php`, namespace `OCA\HtmlViewer\Tests\Unit\…`, config `tests/phpunit.xml`.
- `tests/phpunit.xml` fails on warnings, notices, deprecations and risky tests. Use `createStub()` for doubles without expectations;
  use `#[AllowMockObjectsWithoutExpectations]` only for mocks created in `setUp()` that not every test sets expectations on.
- Classes outside OCP get minimal stubs in `tests/stubs/` (classmap autoloaded): `OCA\Viewer\Event\LoadViewer`, `OC\Security\CSP\ContentSecurityPolicyNonceManager`.
- Code that needs a running server cannot be unit-tested:
  - `OCP\Util` (scripts, translations) – wrap it in a service like `AssetService` and inject the interface.
  - `Response::getHeaders()` – read the protected `headers` property via reflection.
  - `App::__construct()` – create `Application` with `ReflectionClass::newInstanceWithoutConstructor()`.
- `tests/phpunit.integration.xml` is unused; there are no integration tests.

## CI

GitLab ([.gitlab-ci.yml](.gitlab-ci.yml)), image `nextcloud-builder:35`.
The `PHPUnit` job (stage `testing`) runs on every branch with `composer install` and `vendor/bin/phpunit -c tests/phpunit.xml`, natively in the image.
Build, signing and publishing jobs only run on `testing` and `stable`.

## Conventions

- Services are injected through interfaces (`lib/Service/*Interface.php`), registered with `registerServiceAlias()` in `lib/AppInfo/Application.php`.
- Data crosses service boundaries as readonly DTOs in `lib/Dto/`, not arrays.
- Never expose internal file IDs: the direct editing page identifies files only by their direct editing token.
- Direct editing endpoints are public pages authorized by the token in the `X-HtmlViewer-Token` header; keep brute force protection and rate limits on them.
- File contents are never delivered as HTML from the Nextcloud origin (`text/plain` + `nosniff`).
- PHP files start with the app's copyright header and `declare(strict_types=1);`.
- Add user-visible changes to `CHANGELOG.md`; update `README.md` and `appinfo/info.xml` together when settings change.
