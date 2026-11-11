<?php

declare(strict_types=1);

namespace AcfPhoneNumberField\Tests\Integration;

use AcfPhoneNumberField\Field;

final class TranslationTest extends \WP_UnitTestCase
{
    public function testBundledTranslationsAndEnglishFallback(): void
    {
        $domain = 'acf-phone-number-field';
        self::assertSame(0, has_action('init', 'acf_phone_number_field_load_translations'));
        $languages = static fn(array $available): array => array_merge($available, ['sv_SE']);
        add_filter('get_available_languages', $languages);
        $switcher = new \WP_Locale_Switcher();
        remove_filter('get_available_languages', $languages);
        $switcher->init();

        // Isolate the bundled catalog from any site-wide language pack.
        $excludeSitePack = static fn($files, string $path) => (
            $path === (string) WP_LANG_DIR . '/plugins/' ? [] : $files
        );
        add_filter('pre_get_language_files_from_path', $excludeSitePack, 10, 2);
        $loadedPaths = [];
        $capture = static function (string $path, string $loadedDomain) use (&$loadedPaths, $domain): string {
            if ($loadedDomain === $domain) {
                $loadedPaths[] = $path;
            }
            return $path;
        };
        add_filter('load_textdomain_mofile', $capture, 10, 2);
        $field = null;
        try {
            self::assertSame('Phone number', __('Phone number', $domain));
            self::assertTrue($switcher->switch_to_locale('sv_SE'));
            $field = (new \ReflectionClass(Field::class))->newInstanceWithoutConstructor();
            $field->initialize();
            $pluginFile = getenv('ACF_TEST_PLUGIN_FILE') ?: dirname(__DIR__, 2) . '/acf-phone-number-field.php';
            self::assertContains(dirname($pluginFile) . '/languages/' . $domain . '-sv_SE.mo', $loadedPaths);
            self::assertSame('Telefonnummer', $field->label);
            self::assertSame('Använd siffror, mellanslag, bindestreck, punkter och parenteser. + får bara stå först.', $field->validate_value(
                true,
                '070ABC4567',
                [],
                '',
            ));
            self::assertSame('Kontrollera landsnumret och använd + eller 00 för internationella nummer.', $field->validate_value(
                true,
                '+999 123456789',
                [],
                '',
            ));
            self::assertSame('Kontrollera antalet siffror i telefonnumret.', $field->validate_value(
                true,
                '22222222222',
                [],
                '',
            ));
            ob_start();
            $field->render_field(['id' => 'phone', 'value' => '']);
            $html = ob_get_clean();
            self::assertIsString($html);
            self::assertStringContainsString('Använd mellanslag, bindestreck, punkter eller parenteser.', $html);
            self::assertStringContainsString('med standardregion SE', $html);
            self::assertSame('en_US', $switcher->restore_previous_locale());
            $field->initialize();
            self::assertSame('Phone number', $field->label);
            self::assertSame('Use digits, spaces, hyphens, dots and parentheses, with + only at the start.', $field->validate_value(
                true,
                '070ABC4567',
                [],
                '',
            ));
        } finally {
            $switcher->restore_current_locale();
            remove_filter('locale', [$switcher, 'filter_locale']);
            remove_filter('determine_locale', [$switcher, 'filter_locale']);
            if ($field !== null) {
                remove_filter('acf/pre_update_value', [$field, 'pre_update_value']);
            }
            remove_filter('pre_get_language_files_from_path', $excludeSitePack, 10);
            remove_filter('load_textdomain_mofile', $capture, 10);
        }
    }
}
