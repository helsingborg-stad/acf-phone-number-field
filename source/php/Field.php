<?php

declare(strict_types=1);

namespace AcfPhoneNumberField;

final class Field extends \acf_field_text
{
    public function initialize(): void
    {
        parent::initialize();
        $this->name = 'municipio_phone_number';
        $this->label = __('Phone number', 'acf-phone-number-field');
        $this->defaults['default_region'] = 'SE';
        $this->defaults['return_format'] = 'e164';
    }

    public function update_value($value, $postId, $field): mixed
    {
        return (new PhoneValidator())->normalize($value, $field['default_region'] ?? 'SE');
    }

    public function format_value($value, $postId, $field): mixed
    {
        return (new PhoneFormatter())->format(
            $value,
            $field['return_format'] ?? 'e164',
            $field['default_region'] ?? 'SE',
        );
    }

    public function validate_value($valid, $value, $field, $input)
    {
        if ($valid !== true) {
            return $valid;
        }
        $error = (new PhoneValidator())->validate($value, $field['default_region'] ?? 'SE');
        return match ($error) {
            PhoneValidator::UNSUPPORTED_CHARACTERS => __(
                'Use digits, spaces, hyphens, dots and parentheses, with + only at the start.',
                'acf-phone-number-field',
            ),
            PhoneValidator::INVALID_NUMBER => __(
                'Enter a valid phone number. For international numbers, include the country code with + or 00.',
                'acf-phone-number-field',
            ),
            default => true,
        };
    }
}
