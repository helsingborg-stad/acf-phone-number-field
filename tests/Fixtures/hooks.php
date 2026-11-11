<?php

declare(strict_types=1);

// Only capture our registration callback; this does not emulate WordPress dispatch.
function add_action(string $hook, callable $callback): void
{
    phone_test_actions($callback);
}

/** @return list<callable> */
function phone_test_actions(?callable $callback = null): array
{
    static $actions = [];
    if ($callback !== null) {
        $actions[] = $callback;
    }
    return $actions;
}
