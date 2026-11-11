<?php

declare(strict_types=1);

namespace AcfPhoneNumberField\Tests\Unit;

use AcfPhoneNumberField\PhoneValidator;
use PHPUnit\Framework\TestCase;

final class PhoneNormalizationTest extends TestCase
{
    /** @dataProvider rejectedNormalization */
    public function testNormalizationDoesNotRepairOrDiscardRejectedValues(mixed $input): void
    {
        self::assertSame($input, (new PhoneValidator())->normalize($input));
    }

    public static function rejectedNormalization(): array
    {
        return [['070ABC4567'], ['++46701234567'], ['123'], ['+46 10 000 00 00'], [''], [null], [[]], [0]];
    }

    public function testNormalizationIsIdempotent(): void
    {
        $validator = new PhoneValidator();
        $value = '+46701234567';
        self::assertSame($value, $validator->normalize($value));
        self::assertSame($value, $validator->normalize($validator->normalize($value)));
        self::assertSame($value, $validator->normalize($validator->normalize($validator->normalize($value))));
    }
}
