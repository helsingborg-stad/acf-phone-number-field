<?php

declare(strict_types=1);

namespace AcfPhoneNumberField\Tests\Integration;

final class WordPressLifecycleTest extends \WP_UnitTestCase
{
    public function testGivenCoreLifecycleWhenPostIsCreatedThenWordPressPersistsIt(): int
    {
        self::assertFalse(function_exists('acf'), 'First phase must not load ACF.');
        $postId = self::factory()->post->create(['post_title' => 'PHPUnit 9.6 lifecycle probe']);
        self::assertIsInt($postId);
        $post = get_post($postId);
        self::assertInstanceOf(\WP_Post::class, $post);
        self::assertSame('PHPUnit 9.6 lifecycle probe', $post->post_title);
        return $postId;
    }

    /**
     * @depends testGivenCoreLifecycleWhenPostIsCreatedThenWordPressPersistsIt
     */
    public function testGivenPreviousTestWhenCoreTeardownCompletesThenItsPostIsAbsent(int $postId): void
    {
        self::assertNull(get_post($postId));
    }
}
