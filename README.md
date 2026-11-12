# ACF Phone Number Field

## Requirements and installation

- PHP 8.3+.
- WordPress and Advanced Custom Fields (ACF) Free 6.8.11. The field has been verified with WordPress 7.1.4; other versions are not yet verified.
- The plugin's Composer dependencies, including libphonenumber, must be available through its own or a shared autoloader.

Load `acf-phone-number-field.php` as a WordPress plugin, or through the site's loader for a nested MU-plugin installation. Without ACF, field registration is safely skipped.

## How it works

With Default Region `SE`, different input formats can represent the same number:

```text
Input                   Validate and normalize      Stored (E.164)
0701234567          \
070-123 45 67        +--> valid number ----------> +46701234567
+46 70 123 45 67    /                                    |
                                                        +--> Editor display: 070-123 45 67 (auto)
                                                        +--> Return value:   +46701234567 (default)
```

Input may use different formats, but it must pass validation before it is stored. Display Format controls the editor; Return Format controls the value returned by ACF.

## Usage

In an ACF field group, add a phone number field (`municipio_phone_number`) and configure its settings. For a field named `contact_phone`, retrieve its formatted value using ACF:

```php
$phone = get_field('contact_phone', $postId);
```

The return value follows the field's Return Format setting. Pass `false` as the third argument to retrieve the stored value without return formatting:

```php
$storedPhone = get_field('contact_phone', $postId, false);
```

## Phone field settings

| Setting | Default | Choices |
| --- | --- | --- |
| Default Region | `SE` | Supported ISO alpha-2 region codes |
| Display Format | `auto` | `auto`, `national`, `international`, `e164` |
| Return Format | `e164` | `e164`, `national`, `international`, `array` |

Automatic display is national for Swedish numbers only when Default Region is `SE`; otherwise it is international, retaining the country code for safe resaving. The array return contains `e164`, `national`, `international`, integer `country_code` and `region`.

An exposed ACF field group follows ACF REST conventions: `acf_format=light` returns the stored value; `acf_format=standard` uses Return Format. This plugin does not enable field-group REST exposure.

## Behavior and limitations

Storage is always E.164 for accepted non-empty values. Validation checks characters before libphonenumber's numbering rules. Leading `00` means international regardless of Default Region. Validation does **not** establish that a number exists, is reachable or belongs to its submitter. ACF owns required-value handling.

Empty and invalid/unparseable legacy values are returned unchanged for every Return Format and display policy; they are never turned into a valid-looking phone array. Invalid programmatic `update_field()` calls return `false` without changing stored value/reference metadata. Empty updates follow ACF's normal clearing/deletion semantics.

English source strings are the fallback; Swedish (`sv_SE`) is bundled. WordPress language packs take precedence. ACF caches translated field-type labels and descriptions when it initializes them, so they are not refreshed by a later locale switch within the same request. Validation messages use the current locale.

## Development

Development requires PHP 8.3+ with curl, dom, mbstring, mysqli, xml and xmlwriter, Composer 2, curl CLI and tar. MariaDB is required for integration tests only.

For a development checkout:

```sh
composer install
composer test
```

### Commands

Run these from the repository root:

| Command | Purpose |
| --- | --- |
| `composer install` | Install development dependencies. |
| `composer test` | Run unit tests without WordPress or a database. |
| `composer setup:tests` | Download checksum-verified WordPress and ACF test dependencies to `.cache/tests/`; does not change a database. |
| `composer test:integration` | Run integration tests; see the database setup below. |
| `composer test:all` | Run unit and integration tests. |
| `composer lint` | Run linting and static analysis; run `composer setup:tests` first. |
| `composer format` | Format PHP. |
| `composer i18n` | Update translation catalogs; see Translations below. |

The Mago launcher downloads its platform binary on first use and requires GitHub Releases access.

### Integration tests

Create a dedicated disposable MariaDB database and a separate password-protected test account with privileges on that database. Inherited PUBLIC rights on `test` and `test_%` are accepted locally; tests must use only the designated database.

Keep the connection in a private INI file outside version control with permissions 0600:

```ini
[client]
host=127.0.0.1
port=3306
user=YOUR_TEST_ACCOUNT
password="YOUR_TEST_PASSWORD"
database=YOUR_DISPOSABLE_DATABASE
```

**The WordPress harness can delete and recreate test tables. Never use an existing site database.** The test-table prefix is `acf_phone_tests_`.

```sh
export ACF_TEST_DB_CONFIG=/absolute/path/to/private-client.ini
export ACF_TEST_DB_CONFIRM=YOUR_DISPOSABLE_DATABASE
composer setup:tests
composer test:integration
```

The bootstrap rejects missing or malformed configuration, an administrator named `root`, and a confirmation that does not exactly match the configured database name. It does not provision databases or alter grants. `composer test:integration` runs the WordPress lifecycle without ACF and then the phone-field tests with ACF in separate PHP processes.

Optionally, `WP_TESTS_DIR` can point to `tests/phpunit` in a full wordpress-develop checkout, and `ACF_PLUGIN_FILE` to ACF's `acf.php`.

## Translations

To update the translation catalog, install WP-CLI with its `i18n` commands and run `composer i18n` from the repository root. Edit `languages/acf-phone-number-field-sv_SE.po`, then run `composer i18n` again. Review and commit the resulting POT, PO and MO files.

## License

MIT. See [LICENSE](LICENSE).
