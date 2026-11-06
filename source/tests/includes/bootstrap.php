<?php

// Get around direct access blockers.
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../../../');
}

define('ACF_PHONE_NUMBER_FIELD_PATH', __DIR__ . '/../../../');
define('ACF_PHONE_NUMBER_FIELD_URL', 'https://example.com/wp-content/plugins/' . 'modularity-acf-phone-number-field');
define('ACF_PHONE_NUMBER_FIELD_TEMPLATE_PATH', ACF_PHONE_NUMBER_FIELD_PATH . 'templates/');


// Register the autoloader
$loader = require __DIR__ . '/../../../vendor/autoload.php';
$loader->addPsr4('AcfPhoneNumberField\\Test\\', __DIR__ . '/../php/');

require_once __DIR__ . '/PluginTestCase.php';
