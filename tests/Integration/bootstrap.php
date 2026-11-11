<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$testsDir = getenv('WP_TESTS_DIR') ?: dirname(__DIR__, 2) . '/.cache/tests/wordpress-develop-7.1.4/tests/phpunit';
if (!is_readable($testsDir . '/includes/bootstrap.php')) {
    throw new RuntimeException('WordPress test sources are missing. Run composer setup:tests or set WP_TESTS_DIR.');
}

define('WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php');
require_once $testsDir . '/includes/functions.php';

if (getenv('ACF_TEST_LOAD_ACF') === '1') {
    $acfFile = getenv('ACF_PLUGIN_FILE') ?: dirname(__DIR__, 2) . '/.cache/tests/acf-6.8.11/acf.php';
    if (!is_readable($acfFile)) {
        throw new RuntimeException('ACF test sources are missing. Run composer setup:tests or set ACF_PLUGIN_FILE.');
    }

    tests_add_filter('muplugins_loaded', static function () use ($acfFile): void {
        require_once $acfFile;
        require_once dirname(__DIR__, 2) . '/acf-phone-number-field.php';
    });
}

require_once $testsDir . '/includes/bootstrap.php';
