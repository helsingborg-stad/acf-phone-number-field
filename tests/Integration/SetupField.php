<?php

declare(strict_types=1);

namespace AcfPhoneNumberField\Tests\Integration;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

// Test-only save behavior; this is not the production phone field or its full contract.
final class SetupField extends \acf_field_text
{
    public function initialize(): void
    {
        parent::initialize();
        $this->name = 'acf_phone_setup';
        $this->label = 'Setup phone field (tests only)';
    }

    public function update_value($value, $postId, $field): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('The setup field expects a phone string.');
        }
        $phoneUtil = PhoneNumberUtil::getInstance();
        return $phoneUtil->format($phoneUtil->parse($value, 'SE'), PhoneNumberFormat::E164);
    }
}
