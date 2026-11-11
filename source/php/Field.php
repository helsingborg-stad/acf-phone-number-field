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
        $this->defaults['display_format'] = 'auto';
        $this->description = __('Validated phone number stored as E.164.', 'acf-phone-number-field');
        $this->preview_image = false;
        $this->doc_url = false;
    }

    public function render_field($field): void
    {
        $attributes = array_intersect_key($field, array_flip(['id', 'class', 'name', 'placeholder', 'readonly', 'disabled', 'required']));
        $attributes['type'] = 'tel';
        $attributes['autocomplete'] = 'tel';
        $attributes['value'] = (new PhoneFormatter())->display(
            $field['value'],
            $field['display_format'] ?? 'auto',
            $field['default_region'] ?? 'SE',
        );
        $helpId = $field['id'] . '-phone-help';
        $attributes['aria-describedby'] = $helpId;
        echo acf_get_text_input($attributes);
        echo '<p class="description" id="' . esc_attr($helpId) . '">' . esc_html__(
            'For international numbers, include the country code with + or 00.',
            'acf-phone-number-field',
        ) . '</p>';
    }

    public function render_field_settings($field): void
    {
        parent::render_field_settings($field);
        $regions = \libphonenumber\PhoneNumberUtil::getInstance()->getSupportedRegions();
        acf_render_field_setting($field, [
            'label' => __('Default Region', 'acf-phone-number-field'),
            'type' => 'select',
            'name' => 'default_region',
            'choices' => array_combine($regions, $regions),
        ]);
        $formats = [
            'e164' => 'E.164',
            'national' => __('National', 'acf-phone-number-field'),
            'international' => __('International', 'acf-phone-number-field'),
        ];
        acf_render_field_setting($field, [
            'label' => __('Display Format', 'acf-phone-number-field'),
            'type' => 'select',
            'name' => 'display_format',
            'choices' => ['auto' => __('Automatic (national for Sweden)', 'acf-phone-number-field')] + $formats,
        ]);
        acf_render_field_setting($field, [
            'label' => __('Return Format', 'acf-phone-number-field'),
            'type' => 'select',
            'name' => 'return_format',
            'choices' => $formats + ['array' => __('Array', 'acf-phone-number-field')],
        ]);
    }

    public function render_field_validation_settings($field): void
    {
        // Phone rules replace the text field's optional character limit.
    }

    public function render_field_presentation_settings($field): void
    {
        acf_render_field_setting($field, [
            'label' => __('Placeholder Text', 'acf-phone-number-field'),
            'type' => 'text',
            'name' => 'placeholder',
        ]);
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
