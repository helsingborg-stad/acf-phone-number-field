<?php

/**
 * Plugin Name:       ACF Phone Number Field
 * Plugin URI:        https://github.com/helsingborg-stad/acf-phone-number-field
 * Description:       A phone number field for Advanced Custom Fields.
 * Version:           1.0.0
 * Author:            Nikolas Ramstedt
 * Author URI:        https://github.com/helsingborg-stad
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       acf-phone-number-field
 * Domain Path:       /languages
 */

use AcfService\Implementations\NativeAcfService;
use WpService\Implementations\NativeWpService;
use WpUtilService\WpUtilService;

 // Protect agains direct file access
if (! defined('WPINC')) {
    die;
}

define('ACF_PHONE_NUMBER_FIELD_PATH', plugin_dir_path(__FILE__));
define('ACF_PHONE_NUMBER_FIELD_URL', plugins_url('', __FILE__));
define('ACF_PHONE_NUMBER_FIELD_TEMPLATE_PATH', ACF_PHONE_NUMBER_FIELD_PATH . 'templates/');
define('ACF_PHONE_NUMBER_FIELD_TEXT_DOMAIN', 'acf-phone-number-field');

load_plugin_textdomain(ACF_PHONE_NUMBER_FIELD_TEXT_DOMAIN, false, ACF_PHONE_NUMBER_FIELD_PATH . '/languages');

require_once ACF_PHONE_NUMBER_FIELD_PATH . 'Public.php';

// Register the autoloader
require __DIR__ . '/vendor/autoload.php';

// Acf auto import and export
add_action('acf/init', function () {
    $acfExportManager = new \AcfExportManager\AcfExportManager();
    $acfExportManager->setTextdomain('acf-phone-number-field');
    $acfExportManager->setExportFolder(ACF_PHONE_NUMBER_FIELD_PATH . 'source/php/AcfFields/');
    $acfExportManager->autoExport(array(
        'acf-phone-number-field-settings' => 'group_61ea7a87e8aaa' //Update with acf id here, settings view
    ));
    $acfExportManager->import();
});

$wpService = new NativeWpService();
$wpUtilService = new WpUtilService($wpService);

// Start application
new CustomShortLinks\App();

// Start application
new AcfPhoneNumberField\App($wpUtilService->enqueue(__DIR__), $wpService, new NativeAcfService());
