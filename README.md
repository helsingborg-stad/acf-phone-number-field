# ACF Phone Number Field

Standalone `municipio_phone_number` field derived from ACF's text field, with strict phone validation, E.164 storage and configurable display/return formats.

Release packaging and deployment verification remain separate from field implementation.

## Requirements

- PHP 8.3+ with curl, dom, mbstring, mysqli, xml and xmlwriter.
- Composer 2, curl CLI and tar.
- WordPress and ACF Free 6.8.11 for the field (verified with WordPress 7.1.4; other versions are not yet verified). Without ACF, registration is safely skipped.
- MariaDB for integration tests only.

PHP tests require no Node, Docker, web server or IDE. `package.json` and its lockfile are placeholders for future browser-test tooling; there is no frontend build.

The first verified matrix is PHP 8.3.36, PHPUnit 9.6.38, Polyfills 1.1.5, WordPress 7.1.4, ACF Free 6.8.11, libphonenumber 9.0.43 and MariaDB 12.3.2. Mago is pinned to 1.59.0. Other supported PHP versions are not yet verified.

## Install and test

```sh
composer install
composer test
composer setup:tests
composer lint
```

`composer test` and `composer test:unit` run behavior tests without WordPress or a database. `composer setup:tests` downloads checksum-verified WordPress and ACF archives to `.cache/tests/`; it does not change a database.

`composer lint` runs Mago linting and static analysis on the plugin and tests. Run `composer setup:tests` first so the analyzer can resolve WordPress/ACF symbols. Mago's Composer launcher downloads its platform binary on first use, so initial execution requires GitHub Releases access.

Use `composer format` to format PHP.

## Integration database

Create a dedicated disposable database and a separate password-protected test account. Grant that account privileges on the test database. Exclusive access is not required: inherited PUBLIC rights on `test` and `test_%` are accepted for the local environment. Tests must still use only the designated database.

Keep the connection in a private INI file outside version control with permissions 0600:

```ini
[client]
host=127.0.0.1
port=3306
user=YOUR_TEST_ACCOUNT
password="YOUR_TEST_PASSWORD"
database=YOUR_DISPOSABLE_DATABASE
```

**The WordPress harness can delete and recreate test tables. Never use an existing site database.** The configured test-table prefix is `acf_phone_tests_`.

```sh
export ACF_TEST_DB_CONFIG=/absolute/path/to/private-client.ini
export ACF_TEST_DB_CONFIRM=YOUR_DISPOSABLE_DATABASE
composer test:integration
composer test:all
```

The bootstrap rejects missing or malformed configuration, an administrator named `root`, and a confirmation that does not exactly match the configured database name. It does not provision databases or alter grants.

`composer test:integration` runs two separate PHP processes: the unmodified WordPress lifecycle without ACF, then the production phone field with real ACF validation, E.164 save/read behavior, foreign-number rendering/resaving, REST formatting/schema and teardown. Phone-rule and format matrices run only in the fast unit suite.

Optional `WP_TESTS_DIR` points to `tests/phpunit` in a full wordpress-develop checkout; optional `ACF_PLUGIN_FILE` points to ACF's `acf.php`.

## Translations

English source strings are the fallback; Swedish (`sv_SE`) is bundled in `languages/`. At `init` the plugin registers its bundled language directory with WordPress, so it works from either a normal plugin directory or a nested MU-plugin directory. WordPress language packs take precedence. No translation tools are needed at runtime. ACF translates field-type labels/descriptions when it initializes them; those cached metadata are not refreshed by a later locale switch within the same request. Validation messages use the current locale.

To maintain the catalog, install WP-CLI with its `i18n` commands (no WordPress installation is needed for these commands), then run from the repository root:

```sh
composer i18n
```

This extracts the main plugin file and `source/php` into the POT, merges it into the Swedish PO without discarding existing translations, and compiles the MO. Edit `languages/acf-phone-number-field-sv_SE.po`, preserving placeholders and contexts, then run `composer i18n` again to compile the changes. Review the POT and PO diff for new, fuzzy or obsolete entries and check in all three files. This is a manual command, not an install hook.

`composer test:integration` exercises the registered path, bundled MO, a Swedish field label and validation message, and English fallback through real WordPress. To check an installed copy, activate Swedish and confirm the phone field label and an invalid-number message; repeat with English to check fallback. For a nested MU installation, load the plugin through the site's MU loader before checking in the same way.

## Phone field settings

| Setting | Default | Choices |
| --- | --- | --- |
| Default Region | `SE` | Supported ISO alpha-2 region codes |
| Display Format | `auto` | `auto`, `national`, `international`, `e164` |
| Return Format | `e164` | `e164`, `national`, `international`, `array` |

Automatic display is national for Swedish numbers only when Default Region is `SE`; otherwise it is international, retaining the country code for safe resaving. Storage is always E.164 for accepted non-empty values; `get_field('contact_phone', $postId, false)` retrieves it without return formatting.

The array return contains `e164`, `national`, `international`, integer `country_code` and `region`. An exposed ACF field group follows ACF REST conventions: `acf_format=light` returns the stored value; `acf_format=standard` uses Return Format. This plugin does not enable field-group REST exposure.

Empty and invalid/unparseable legacy values are returned unchanged for every Return Format and display policy; they are never turned into a valid-looking phone array. Invalid programmatic `update_field()` calls return `false` without changing stored value/reference metadata. Empty updates follow ACF's normal clearing/deletion semantics.

Validation checks characters before libphonenumber's numbering rules. Leading `00` means international regardless of Default Region. Validation does **not** establish that a number exists, is reachable or belongs to its submitter. ACF owns required-value handling.

## Deployment build hook

`php build.php` installs production Composer dependencies and generates an authoritative optimized autoloader. It runs from the plugin directory even when invoked from elsewhere. It does not build frontend assets. For development and tests, use `composer install` instead: the production build omits development dependencies.

The parent deployment discovers this hook and may pass:

- `--no-composer`: skip dependency installation when the parent provides the shared autoloader.
- `--install-npm`: accepted for parent-build compatibility; no action is needed for this PHP-only package.
- `--cleanup`: remove Git metadata, test caches, tests, tool configuration and build/dependency manifests from the build copy, retaining runtime code, licenses and installed runtime dependencies.

**Run `--cleanup` only in a disposable build copy, never in your development checkout.** This hook does not create a release ZIP or verify the MU-plugin loader.

## CI and scope

`.github/workflows/tests.yml` configures pull-request checks using the same Composer commands and a disposable MariaDB service. The service container runs on GitHub; local verification uses native PHP/MariaDB.

A hosted Actions run is still required to verify the runner, service image and Actions environment. Devcontainer verification, browser tests, ACF Pro, MU-loader verification and release packaging are deferred.

The official WordPress harness failed under PHPUnit 11 and 12 because it calls the removed `parseTestMethodAnnotations()` method. PHPUnit 9.6 / Polyfills 1.x passed the real lifecycle without a compatibility adapter or upstream patches.

## License

MIT. See [LICENSE](LICENSE).
