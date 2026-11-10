<?php

/**
 * Plugin Name: ACF Phone Number Field
 * Description: International phone number field for Advanced Custom Fields (under development).
 * Version: 0.1.0
 * Requires PHP: 8.3
 * Author: Nikolas Ramstedt
 * Author URI: https://github.com/helsingborg-stad
 * License: MIT
 * Text Domain: acf-phone-number-field
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

// Composer deployments may already provide the project's autoloader.
if (is_readable(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Product field registration is deferred; the setup field exists only in tests.
