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

// WordPress exposes its textdomain registry as a global.
// @mago-ignore lint:no-global
function acf_phone_number_field_load_translations(): void
{
    $domain = 'acf-phone-number-field';
    load_plugin_textdomain($domain);
    // An absolute custom path works for both plugins and nested MU plugins, including locale switches.
    /** @var WP_Textdomain_Registry $wp_textdomain_registry */
    global $wp_textdomain_registry;
    $wp_textdomain_registry->set_custom_path($domain, __DIR__ . '/languages');
}

add_action('init', 'acf_phone_number_field_load_translations', 0);

add_action('acf/include_field_types', static function (): void {
    if (class_exists('acf_field_text') && class_exists(\AcfPhoneNumberField\Field::class)) {
        acf_register_field_type(\AcfPhoneNumberField\Field::class);
    }
});
