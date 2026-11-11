<?php

declare(strict_types=1);

namespace AcfPhoneNumberField\Tests\Integration;

use AcfPhoneNumberField\Field;

final class FieldLifecycleTest extends \WP_UnitTestCase
{
    private function phoneField(array $settings = [], bool $rest = false): array
    {
        acf_add_local_field_group([
            'key' => 'group_phone',
            'title' => 'Phone behavior',
            'show_in_rest' => $rest,
            'fields' => [array_merge([
                'key' => 'field_phone',
                'name' => 'contact_phone',
                'label' => 'Phone',
                'type' => 'municipio_phone_number',
            ], $settings)],
            'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'post']]],
        ]);
        $field = acf_get_field('field_phone');
        self::assertIsArray($field);
        return $field;
    }

    public function testRegistrationAndValidationThroughAcf(): void
    {
        self::assertInstanceOf(Field::class, acf_get_field_type('municipio_phone_number'));
        $field = $this->phoneField();
        self::assertTrue(acf_validate_value('070-123 45 67', $field, 'acf[field_phone]'));
        self::assertFalse(acf_validate_value('070ABC4567', $field, 'acf[field_phone]'));
        self::assertSame(
            [
                [
                    'input' => 'acf[field_phone]',
                    'message' => 'Use digits, spaces, hyphens, dots and parentheses, with + only at the start.',
                ],
            ],
            acf_get_validation_errors(),
        );

        // Exercise native validation, not just our callback, before deciding on a guard.
        acf_reset_validation_errors();
        $_POST['acf'] = ['field_phone' => ['0701234567']];
        self::assertFalse(acf_validate_save_post());
        self::assertSame(
            [[
                'input' => 'acf[field_phone]',
                'message' => 'Check the phone number. Make sure all digits and any country code are correct.',
            ]],
            acf_get_validation_errors(),
        );
    }

    public function testLengthErrorsReachAcfValidation(): void
    {
        $field = $this->phoneField();
        foreach ([
            '+46 1' => 'The phone number appears to be too short. Check that all digits are included.',
            '0047 406123456' => 'The phone number appears to be too long. Check the number of digits.',
        ] as $value => $message) {
            acf_reset_validation_errors();
            self::assertFalse(acf_validate_value($value, $field, 'acf[field_phone]'));
            self::assertSame([['input' => 'acf[field_phone]', 'message' => $message]], acf_get_validation_errors());
        }
    }

    public function testNonDefaultRegionStorageAndStructuredReturn(): int
    {
        $this->phoneField(['default_region' => 'NO', 'return_format' => 'array']);
        $postId = self::factory()->post->create();
        self::assertIsInt($postId);
        self::assertNotFalse(update_field('field_phone', '406 12 345', $postId));
        self::assertSame('+4740612345', get_post_meta($postId, 'contact_phone', true));
        self::assertSame('+4740612345', get_field('contact_phone', $postId, false));
        self::assertSame(
            [
                'e164' => '+4740612345',
                'national' => '40 61 23 45',
                'international' => '+47 40 61 23 45',
                'country_code' => 47,
                'region' => 'NO',
            ],
            get_field('contact_phone', $postId),
        );
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
        $label = $document->getElementsByTagName('label')->item(0);
        self::assertInstanceOf(\DOMElement::class, $label);
        self::assertSame($input->getAttribute('id'), $label->getAttribute('for'));
        $help = $document->getElementById($input->getAttribute('aria-describedby'));
        self::assertInstanceOf(\DOMElement::class, $help);
        self::assertStringContainsString('country code', $help->textContent);
        self::assertTrue(acf_validate_value($input->getAttribute('value'), $field, 'acf[field_phone]'));
        update_field('field_phone', $input->getAttribute('value'), $postId);
        self::assertSame('+4740612345', get_field('contact_phone', $postId, false));
    }

    public function testRestLightAndStandardResponsesMatchThePublishedSchema(): void
    {
        $this->phoneField(['return_format' => 'array'], true);
        $postId = self::factory()->post->create(['post_status' => 'publish']);
        self::assertIsInt($postId);
        update_field('field_phone', '+46701234567', $postId);
        $expected = [
            'e164' => '+46701234567',
            'national' => '070-123 45 67',
            'international' => '+46 70 123 45 67',
            'country_code' => 46,
            'region' => 'SE',
        ];
        foreach (['light' => '+46701234567', 'standard' => $expected] as $format => $value) {
            $request = new \WP_REST_Request('GET', '/wp/v2/posts/' . $postId);
            $request->set_param('acf_format', $format);
            $response = rest_get_server()->dispatch($request);
            self::assertSame(200, $response->get_status());
            /** @var array{acf: array{contact_phone: mixed}} $data */
            $data = $response->get_data();
            self::assertSame($value, $data['acf']['contact_phone']);
        }
        $response = rest_get_server()->dispatch(new \WP_REST_Request('OPTIONS', '/wp/v2/posts/' . $postId));
        self::assertSame(200, $response->get_status());
        /** @var array{schema: array{properties: array{acf: array{properties: array{contact_phone: array}}}}} $data */
        $data = $response->get_data();
        $schema = $data['schema']['properties']['acf']['properties']['contact_phone'];
        self::assertTrue(rest_validate_value_from_schema('+46701234567', $schema));
        self::assertTrue(rest_validate_value_from_schema($expected, $schema));
        self::assertSame(
            [
                'e164' => ['type' => 'string'],
                'national' => ['type' => 'string'],
                'international' => ['type' => 'string'],
                'country_code' => ['type' => 'integer'],
                'region' => ['type' => 'string'],
            ],
            $schema['properties'],
        );
    }

    public function testRestRejectsInvalidWritesBeforeSavingAndAcceptsValidRegionalInput(): void
    {
        $this->phoneField(['default_region' => 'NO'], true);
        $postId = self::factory()->post->create(['post_status' => 'publish']);
        self::assertIsInt($postId);
        $userId = self::factory()->user->create(['role' => 'administrator']);
        self::assertIsInt($userId);
        wp_set_current_user($userId);
        update_field('field_phone', '+46701234567', $postId);

        $request = new \WP_REST_Request('POST', '/wp/v2/posts/' . $postId);
        $request->set_body_params(['acf' => ['contact_phone' => 'not a phone']]);
        // Rebuild core's route arguments after ACF's request-specific schema registration.
        rest_get_server()->dispatch(new \WP_REST_Request('GET', '/wp/v2/posts/' . $postId));
        $server = rest_get_server();
        self::assertInstanceOf(\Spy_REST_Server::class, $server);
        $server->override_by_default = true;
        do_action('rest_api_init', $server);
        $server->override_by_default = false;
        $response = rest_get_server()->dispatch($request);
        self::assertSame(400, $response->get_status());
        /** @var array{data: array{params: array{acf: string}}} $error */
        $error = $response->get_data();
        self::assertStringContainsString('Use digits', $error['data']['params']['acf']);
        self::assertSame('+46701234567', get_post_meta($postId, 'contact_phone', true));

        $request = new \WP_REST_Request('POST', '/wp/v2/posts/' . $postId);
        $request->set_body_params(['acf' => ['contact_phone' => '406 12 345']]);
        $response = rest_get_server()->dispatch($request);
        self::assertSame(200, $response->get_status());
        self::assertSame('+4740612345', get_post_meta($postId, 'contact_phone', true));
    }

    public function testRejectedProgrammaticUpdatesPreserveDataAndEmptyUpdatesCanClearIt(): void
    {
        $this->phoneField(['return_format' => 'array']);
        $postId = self::factory()->post->create();
        self::assertIsInt($postId);
        update_field('field_phone', '+46701234567', $postId);
        get_field('contact_phone', $postId); // Warm ACF's formatted-value cache before rejection.
        self::assertFalse(update_field('field_phone', ['0701234567'], $postId));
        self::assertSame('+46701234567', get_post_meta($postId, 'contact_phone', true));
        self::assertSame('field_phone', get_post_meta($postId, '_contact_phone', true));
        self::assertSame('+46701234567', get_field('contact_phone', $postId, false));

        $newPostId = self::factory()->post->create();
        self::assertIsInt($newPostId);
        self::assertFalse(update_field('field_phone', 'not a phone', $newPostId));
        self::assertFalse(metadata_exists('post', $newPostId, 'contact_phone'));
        self::assertFalse(metadata_exists('post', $newPostId, '_contact_phone'));

        update_field('field_phone', '', $postId);
        self::assertSame('', get_field('contact_phone', $postId));
        self::assertSame('', get_post_meta($postId, 'contact_phone', true));

        update_post_meta($postId, 'contact_phone', 'old phone entry');
        acf_flush_value_cache($postId, 'contact_phone');
        self::assertSame('old phone entry', get_field('contact_phone', $postId));
        self::assertSame('old phone entry', get_post_meta($postId, 'contact_phone', true));
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
