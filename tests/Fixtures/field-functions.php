<?php

declare(strict_types=1);

namespace AcfPhoneNumberField;

function __(string $text, string $domain): string
{
    return $text;
}

// Match the WordPress function name used by the field.
// @mago-ignore lint:function-name
function esc_html__(string $text, string $domain): string
{
    return htmlspecialchars($text, ENT_QUOTES);
}

function esc_attr(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES);
}

function acf_get_text_input(array $attributes): string
{
    assert(is_string($attributes['id']), 'The input ID must be a string.');
    assert(is_string($attributes['aria-describedby']), 'The help association must be a string.');
    return (
        '<input id="'
        . esc_attr($attributes['id'])
        . '" aria-describedby="'
        . esc_attr($attributes['aria-describedby'])
        . '">'
    );
}
