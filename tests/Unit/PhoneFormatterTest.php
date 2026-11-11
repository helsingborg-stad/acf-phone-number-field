<?php

declare(strict_types=1);

namespace AcfPhoneNumberField\Tests\Unit;

use AcfPhoneNumberField\PhoneFormatter;
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
            ['array', [
                'e164' => '+46701234567',
                'national' => '070-123 45 67',
                'international' => '+46 70 123 45 67',
                'country_code' => 46,
                'region' => 'SE',
            ]],
        ];
    }

    /** @dataProvider displayValues */
    public function testDisplayPolicy(string $value, string $format, string $expected): void
    {
        self::assertSame($expected, (new PhoneFormatter())->display($value, $format));
    }

    public static function displayValues(): array
    {
        return [
            ['+46701234567', 'national', '070-123 45 67'],
            ['+46701234567', 'international', '+46 70 123 45 67'],
            ['+46701234567', 'e164', '+46701234567'],
            ['+46701234567', 'auto', '070-123 45 67'],
            ['+4740612345', 'auto', '+47 40 61 23 45'],
        ];
    }
}
