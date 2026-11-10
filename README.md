# ACF Phone Number Field

PHP/ACF plugin under development. The repository provides a local PHP test foundation; the production phone field is not implemented. Do not deploy this setup as a finished phone-field plugin.

## Requirements

- PHP 8.3+ with curl, dom, mbstring, mysqli, xml and xmlwriter.
- Composer 2, curl CLI and tar.
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

`composer test:integration` runs two separate PHP processes: the unmodified WordPress lifecycle without ACF, then real ACF registration, E.164 save/read behavior and teardown. The E.164 implementation is a minimal test-only field, not the production phone-field contract.

Optional `WP_TESTS_DIR` points to `tests/phpunit` in a full wordpress-develop checkout; optional `ACF_PLUGIN_FILE` points to ACF's `acf.php`.

## CI and scope

`.github/workflows/tests.yml` configures pull-request checks using the same Composer commands and a disposable MariaDB service. The service container runs on GitHub; local verification uses native PHP/MariaDB.

A hosted Actions run is still required to verify the runner, service image and Actions environment. Devcontainer verification, browser tests, ACF Pro, MU-loader verification and release packaging are deferred.

The official WordPress harness failed under PHPUnit 11 and 12 because it calls the removed `parseTestMethodAnnotations()` method. PHPUnit 9.6 / Polyfills 1.x passed the real lifecycle without a compatibility adapter or upstream patches.

## License

MIT. See [LICENSE](LICENSE).
