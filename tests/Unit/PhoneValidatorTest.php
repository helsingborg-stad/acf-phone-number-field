<?php

declare(strict_types=1);

namespace AcfPhoneNumberField\Tests\Unit;

use AcfPhoneNumberField\PhoneValidator;
use PHPUnit\Framework\TestCase;

final class PhoneValidatorTest extends TestCase
{
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
            ['070-123 45 67',    'NO'],
            ['+46 10 000 00 00', 'SE'],
            ['123',              'SE'],
            ['()',               'SE'],
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
