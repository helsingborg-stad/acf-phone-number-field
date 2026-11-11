<?php

declare(strict_types=1);

namespace AcfPhoneNumberField\Tests\Unit;

use AcfPhoneNumberField\Field;
use PHPUnit\Framework\TestCase;

final class FieldAdapterTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBootstrapSkipsUnavailableAcf(): void
    {
        require __DIR__ . '/../Fixtures/hooks.php';
        define('ABSPATH', '/unused/');
        require dirname(__DIR__, 2) . '/acf-phone-number-field.php';
        self::assertCount(1, phone_test_actions());
        phone_test_actions()[0]();
        self::assertFalse(class_exists(Field::class, false));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testEarlierValidationFailuresArePreserved(): void
    {
        class_alias((new class {})::class, 'acf_field_text');
        $field = new Field();
        self::assertSame('Earlier error', $field->validate_value('Earlier error', '123', [], 'phone'));
        self::assertFalse($field->validate_value(false, '123', [], 'phone'));
    }
}
