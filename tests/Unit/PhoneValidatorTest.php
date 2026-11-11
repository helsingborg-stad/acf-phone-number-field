<?php

declare(strict_types=1);

namespace AcfPhoneNumberField\Tests\Unit;

use AcfPhoneNumberField\PhoneValidator;
use PHPUnit\Framework\TestCase;

// @mago-ignore lint:too-many-methods
final class PhoneValidatorTest extends TestCase
{
    public function testIntentionalCiFailure(): void
    {
        self::fail('Intentional failure to verify CI reporting.');
    }

    /** @dataProvider accepted */
    public function testSupportedNumbers(string $input, string $region, string $expected): void
    {
        self::assertNull((new PhoneValidator())->validate($input, $region));
        self::assertSame($expected, (new PhoneValidator())->normalize($input, $region));
    }

    public static function accepted(): array
    {
        return [
            ['070-123 45 67',     'SE', '+46701234567'],
            ['042 12 34 56',      'SE', '+4642123456'],
            ['0701234567',        'SE', '+46701234567'],
            ['075-123 45 67',     'SE', '+46751234567'],
            ['074-012 34 56',     'SE', '+46740123456'],
            ['070.123.45.67',     'SE', '+46701234567'],
            ['070 (123) 45 67',   'SE', '+46701234567'],
            ['+46 70 123 45 67',  'SE', '+46701234567'],
            ['0046 70 123 45 67', 'SE', '+46701234567'],
            ['0046 70 123 45 67', 'US', '+46701234567'],
            ['+47 406 12 345',    'SE', '+4740612345'],
            ['0047 406 12 345',   'SE', '+4740612345'],
            ['406 12 345',        'SE', '+4640612345'],
            ['406 12 345',        'NO', '+4740612345'],
            ['+46 70 123 45 67',  'NO', '+46701234567'],
        ];
    }

    /** @dataProvider invalidNumbers */
    public function testInvalidNumbersDoNotThrow(string $input, string $region): void
    {
        self::assertSame(PhoneValidator::INVALID_NUMBER, (new PhoneValidator())->validate($input, $region));
    }

    public static function invalidNumbers(): array
    {
        return [
            ['+46 10 000 00 00', 'SE'],
            ['+999 123456789',   'SE'],
            ['070123456',        'SE'],
            ['07012345678',      'SE'],
            ['+46 70123456',     'NO'],
            ['+46 7012345678',   'NO'],
            ['0046 70123456',    'US'],
            ['0046 7012345678',  'US'],
            ['+46 701234567890', 'SE'],
            ['07512345678',      'SE'],
            ['07401234567',      'SE'],
            ['()',               'SE'],
        ];
    }

    /** @dataProvider lengthErrors */
    public function testLengthErrorsPreserveRejectedValues(string $input, string $region, string $error): void
    {
        $validator = new PhoneValidator();
        self::assertSame($error, $validator->validate($input, $region));
        self::assertSame($input, $validator->normalize($input, $region));
        self::assertNull($validator->parse($input, $region));
    }

    public static function lengthErrors(): array
    {
        return [
            ['123',                         'SE', PhoneValidator::TOO_SHORT],
            ['12',                          'NO', PhoneValidator::TOO_SHORT],
            ['+46 1',                       'NO', PhoneValidator::TOO_SHORT],
            ['0046 1',                      'US', PhoneValidator::TOO_SHORT],
            ['+47 12',                      'SE', PhoneValidator::TOO_SHORT],
            ['0047 12',                     'SE', PhoneValidator::TOO_SHORT],
            ['070-123 45 67',               'NO', PhoneValidator::TOO_LONG],
            ['+47 406123456',               'SE', PhoneValidator::TOO_LONG],
            ['0047 406123456',              'US', PhoneValidator::TOO_LONG],
            ['07012345678901234567890',     'SE', PhoneValidator::TOO_LONG],
            ['+46 7012345678901234567890',  'NO', PhoneValidator::TOO_LONG],
            ['0046 7012345678901234567890', 'US', PhoneValidator::TOO_LONG],
        ];
    }

    /** @dataProvider unsupportedCharacters */
    public function testCharacterCheckPrecedesParsing(string $input): void
    {
        self::assertSame(PhoneValidator::UNSUPPORTED_CHARACTERS, (new PhoneValidator())->validate($input));
    }

    public static function unsupportedCharacters(): array
    {
        return array_map(static fn(string $value): array => [$value], [
            '070ABC4567',
            '070/1234567',
            '+',
            '++46701234567',
            '070+1234567',
            "070\t1234567",
            "0701234567\n",
        ]);
    }

    /** @dataProvider malformed */
    public function testMalformedValuesFailWithoutPhpErrors(mixed $input): void
    {
        self::assertSame(PhoneValidator::INVALID_NUMBER, (new PhoneValidator())->validate($input));
    }

    public static function malformed(): array
    {
        return [[0], [701_234_567], [false], [[]], [['0701234567']], [new \stdClass()]];
    }

    public function testEmptyValuesLeaveRequiredHandlingToAcf(): void
    {
        self::assertNull((new PhoneValidator())->validate(''));
        self::assertNull((new PhoneValidator())->validate(null));
    }
}
