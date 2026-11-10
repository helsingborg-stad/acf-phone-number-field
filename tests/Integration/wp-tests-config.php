<?php

declare(strict_types=1);

// This file also runs in the WordPress install subprocess, before Composer loads.
$configPath = getenv('ACF_TEST_DB_CONFIG');
if ($configPath === false || $configPath === '' || !is_readable($configPath)) {
    throw new RuntimeException('Set ACF_TEST_DB_CONFIG to a readable private MariaDB client INI file.');
}

// INI warnings can include credential text; replace them with a secret-free error.
set_error_handler(static function (int $severity, #[SensitiveParameter] string $message): never {
    throw new RuntimeException('Could not parse private database configuration. Check its INI syntax.');
});
try {
    $config = parse_ini_file($configPath, true, INI_SCANNER_RAW);
} finally {
    restore_error_handler();
}
if (!is_array($config) || !is_array($config['client'] ?? null)) {
    throw new RuntimeException('The private INI file requires a [client] section.');
}
$client = [];
foreach (['host', 'port', 'user', 'password', 'database'] as $key) {
    if (!is_string($config['client'][$key] ?? null) || $config['client'][$key] === '') {
        throw new RuntimeException(
            'The private INI file requires host, port, user, password and database in [client].',
        );
    }
    $client[$key] = $config['client'][$key];
}

if (getenv('ACF_TEST_DB_CONFIRM') !== $client['database']) {
    throw new RuntimeException(
        'Set ACF_TEST_DB_CONFIRM to the disposable database name. WordPress tests can delete its tables.',
    );
}

if ($client['user'] === 'root') {
    throw new RuntimeException('Use the separate test account, not the database administrator.');
}

$testsDir = getenv('WP_TESTS_DIR') ?: dirname(__DIR__, 2) . '/.cache/tests/wordpress-develop-7.1.4/tests/phpunit';
define('ABSPATH', dirname($testsDir, 2) . '/src/');
define('DB_NAME', $client['database']);
define('DB_USER', $client['user']);
define('DB_PASSWORD', $client['password']);
define('DB_HOST', $client['host'] . ':' . $client['port']);
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
define('WP_DEBUG', true);
define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'ACF field tests');
define('WP_PHP_BINARY', escapeshellarg(PHP_BINARY));
define('WPLANG', '');

$table_prefix = 'acf_phone_tests_';
