<?php

declare(strict_types=1);

// Only capture our registration callback; this does not emulate WordPress dispatch.
function add_action(string $hook, callable $callback): void
{
    $GLOBALS['phone_test_actions'][] = $callback;
}
