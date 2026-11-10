<?php

declare(strict_types=1);

namespace AcfPhoneNumberField\Tests\Unit;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\TestCase;

final class PhoneDependencyTest extends TestCase
{
    public function testGivenSwedishInputWhenParsedThenDependencyProducesE164(): void
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        $number = $phoneUtil->parse('070-123 45 67', 'SE');

        self::assertTrue($phoneUtil->isPossibleNumber($number));
        self::assertSame('+46701234567', $phoneUtil->format($number, PhoneNumberFormat::E164));
    }
}
