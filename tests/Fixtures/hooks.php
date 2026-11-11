<?php

declare(strict_types=1);

// Execute only our bootstrap callback; real hook dispatch is covered by ACF integration.
function add_action(string $hook, callable $callback, int $priority = 10): void
{
    if ($hook === 'acf/include_field_types') {
        $callback();
    }
}
