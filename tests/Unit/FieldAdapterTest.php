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
        self::assertFalse(class_exists(Field::class, false));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUpdateGuardPreservesEarlierDecisionsAndIgnoresOtherFieldTypes(): void
    {
        class_alias((new class {
            public string $name = 'municipio_phone_number';
        })::class, 'acf_field_text');
        $field = new Field();
        $phone = ['type' => 'municipio_phone_number'];
        self::assertFalse($field->pre_update_value(false, '0701234567', 1, $phone));
        self::assertTrue($field->pre_update_value(true, 'invalid', 1, $phone));
        self::assertNull($field->pre_update_value(null, 'invalid', 1, ['type' => 'text']));
        self::assertNull($field->pre_update_value(null, null, 1, $phone));
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
        self::assertFalse($field->validate_rest_value(false, '123', []));
    }
}
