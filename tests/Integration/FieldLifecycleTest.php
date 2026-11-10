<?php

declare(strict_types=1);

namespace AcfPhoneNumberField\Tests\Integration;

final class FieldLifecycleTest extends \WP_UnitTestCase
{
    public function testGivenNationalPhoneWhenSavedThroughAcfThenE164IsStoredAndReturned(): int
    {
        self::assertInstanceOf(SetupField::class, acf_get_field_type('acf_phone_setup'));
        acf_add_local_field_group([
            'key' => 'group_acf_phone_setup',
            'title' => 'Setup phone behavior',
            'fields' => [[
                'key' => 'field_acf_phone_setup',
                'name' => 'setup_phone',
                'label' => 'Phone',
                'type' => 'acf_phone_setup',
            ]],
            'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'post']]],
        ]);
        $postId = self::factory()->post->create();
        self::assertIsInt($postId);

        self::assertNotFalse(update_field('field_acf_phone_setup', '070-123 45 67', $postId));

        self::assertSame('+46701234567', get_field('setup_phone', $postId));
        self::assertSame('+46701234567', get_field('setup_phone', $postId, false));
        self::assertSame('+46701234567', get_post_meta($postId, 'setup_phone', true));
        self::assertSame('field_acf_phone_setup', get_post_meta($postId, '_setup_phone', true));
        return $postId;
    }

    /**
     * @depends testGivenNationalPhoneWhenSavedThroughAcfThenE164IsStoredAndReturned
     */
    public function testGivenPreviousTestWhenCoreTeardownCompletesThenItsPhoneMetadataIsAbsent(int $postId): void
    {
        self::assertNull(get_post($postId));
        self::assertSame('', get_post_meta($postId, 'setup_phone', true));
    }

    public function tear_down(): void
    {
        acf_remove_local_field_group('group_acf_phone_setup');
        parent::tear_down();
    }
}
