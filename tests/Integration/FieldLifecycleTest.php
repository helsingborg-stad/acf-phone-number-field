<?php

declare(strict_types=1);

namespace AcfPhoneNumberField\Tests\Integration;

use AcfPhoneNumberField\Field;

final class FieldLifecycleTest extends \WP_UnitTestCase
{
    private function phoneField(array $settings = []): array
    {
        acf_add_local_field_group([
            'key' => 'group_phone',
            'title' => 'Phone behavior',
            'fields' => [array_merge([
                'key' => 'field_phone',
                'name' => 'contact_phone',
                'label' => 'Phone',
                'type' => 'municipio_phone_number',
            ], $settings)],
            'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'post']]],
        ]);
        return acf_get_field('field_phone');
    }

    public function testRegistrationAndValidationThroughAcf(): void
    {
        self::assertInstanceOf(Field::class, acf_get_field_type('municipio_phone_number'));
        $field = $this->phoneField();
        self::assertTrue(acf_validate_value('070-123 45 67', $field, 'acf[field_phone]'));
        self::assertFalse(acf_validate_value('070ABC4567', $field, 'acf[field_phone]'));
        self::assertSame([
            ['input' => 'acf[field_phone]', 'message' => 'Use digits, spaces, hyphens, dots and parentheses, with + only at the start.'],
        ], acf_get_validation_errors());

        // Exercise native validation, not just our callback, before deciding on a guard.
        acf_reset_validation_errors();
        $_POST['acf'] = ['field_phone' => ['0701234567']];
        self::assertFalse(acf_validate_save_post());
        self::assertSame('acf[field_phone]', acf_get_validation_errors()[0]['input']);
    }

    public function testNonDefaultRegionStorageAndStructuredReturn(): int
    {
        $this->phoneField(['default_region' => 'NO', 'return_format' => 'array']);
        $postId = self::factory()->post->create();
        self::assertIsInt($postId);
        self::assertNotFalse(update_field('field_phone', '406 12 345', $postId));
        self::assertSame('+4740612345', get_post_meta($postId, 'contact_phone', true));
        self::assertSame('+4740612345', get_field('contact_phone', $postId, false));
        self::assertSame([
            'e164' => '+4740612345',
            'national' => '40 61 23 45',
            'international' => '+47 40 61 23 45',
            'country_code' => 47,
            'region' => 'NO',
        ], get_field('contact_phone', $postId));
        return $postId;
    }

    public function testForeignNumberCanBeRenderedAndResavedWithoutLosingItsCountryCode(): void
    {
        $field = $this->phoneField();
        $postId = self::factory()->post->create();
        update_field('field_phone', '+4740612345', $postId);
        $field['value'] = get_field('contact_phone', $postId, false);
        ob_start();
        acf_render_field_wrap($field);
        $html = ob_get_clean();
        self::assertIsString($html);
        $document = new \DOMDocument();
        $document->loadHTML($html);
        $input = $document->getElementsByTagName('input')->item(0);
        self::assertInstanceOf(\DOMElement::class, $input);
        self::assertSame('tel', $input->getAttribute('type'));
        self::assertSame('tel', $input->getAttribute('autocomplete'));
        self::assertSame('+47 40 61 23 45', $input->getAttribute('value'));
        self::assertSame($input->getAttribute('id'), $document->getElementsByTagName('label')->item(0)->getAttribute('for'));
        self::assertStringContainsString('country code', $document->getElementById($input->getAttribute('aria-describedby'))->textContent);
        self::assertTrue(acf_validate_value($input->getAttribute('value'), $field, 'acf[field_phone]'));
        update_field('field_phone', $input->getAttribute('value'), $postId);
        self::assertSame('+4740612345', get_field('contact_phone', $postId, false));
    }

    /** @depends testNonDefaultRegionStorageAndStructuredReturn */
    public function testCoreTeardownRemovesPhoneMetadata(int $postId): void
    {
        self::assertNull(get_post($postId));
        self::assertSame('', get_post_meta($postId, 'contact_phone', true));
    }

    public function tear_down(): void
    {
        unset($_POST['acf']);
        acf_reset_validation_errors();
        acf_get_store('fields')->reset();
        acf_remove_local_field('field_phone');
        acf_remove_local_field_group('group_phone');
        parent::tear_down();
    }
}
