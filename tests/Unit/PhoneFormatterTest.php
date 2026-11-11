<?php

declare(strict_types=1);

namespace AcfPhoneNumberField\Tests\Unit;

use AcfPhoneNumberField\PhoneFormatter;
use AcfPhoneNumberField\PhoneValidator;
use PHPUnit\Framework\TestCase;

final class PhoneFormatterTest extends TestCase
{
    /** @dataProvider formats */
    public function testFormatsAreDerivedFromTheSameNumber(string $format, mixed $expected): void
    {
        self::assertSame($expected, (new PhoneFormatter())->format('+46701234567', $format));
    }

    public static function formats(): array
    {
        return [
            ['e164', '+46701234567'],
            ['national', '070-123 45 67'],
            ['international', '+46 70 123 45 67'],
            [
                'array',
                [
                    'e164' => '+46701234567',
                    'national' => '070-123 45 67',
                    'international' => '+46 70 123 45 67',
                    'country_code' => 46,
                    'region' => 'SE',
                ],
            ],
        ];
    }

    /** @dataProvider fallbackValues */
    public function testEmptyAndLegacyValuesArePreserved(mixed $value): void
    {
        $formatter = new PhoneFormatter();
        foreach (['e164', 'national', 'international', 'array'] as $format) {
            self::assertSame($value, $formatter->format($value, $format));
        }
        foreach (['auto', 'e164', 'national', 'international'] as $format) {
            self::assertSame($value, $formatter->display($value, $format));
        }
    }

    public static function fallbackValues(): array
    {
        return [[''], [null], [false], ['old phone entry'], ['()'], ['123']];
    }

    public function testAutomaticSwedishDisplayCanBeResavedWithANonSwedishDefaultRegion(): void
    {
        $number = '+46701234567';
        $display = (new PhoneFormatter())->display($number, 'auto', 'NO');
        self::assertSame('+46 70 123 45 67', $display);
        self::assertNull((new PhoneValidator())->validate($display, 'NO'));
        self::assertSame($number, (new PhoneValidator())->normalize($display, 'NO'));
    }

    /** @dataProvider displayValues */
    public function testDisplayPolicy(string $value, string $format, string $expected): void
    {
        self::assertSame($expected, (new PhoneFormatter())->display($value, $format));
    }

    public static function displayValues(): array
    {
        return [
            ['+46701234567', 'national',      '070-123 45 67'],
            ['+46701234567', 'international', '+46 70 123 45 67'],
            ['+46701234567', 'e164',          '+46701234567'],
            ['+46701234567', 'auto',          '070-123 45 67'],
            ['+4740612345',  'auto',          '+47 40 61 23 45'],
        ];
    }
}
