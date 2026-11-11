<?php

declare(strict_types=1);

namespace AcfPhoneNumberField;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;

final class PhoneValidator
{
    public const UNSUPPORTED_CHARACTERS = 'unsupported_characters';
    public const INVALID_NUMBER = 'invalid_number';

    public function validate(mixed $value, string $region = 'SE'): ?string
    {
        if ($value === '' || $value === null) {
            return null;
        }
        if (!is_string($value)) {
            return self::INVALID_NUMBER;
        }
        if (preg_match('/\A\+?[0-9 ().-]+\z/', $value) !== 1) {
            return self::UNSUPPORTED_CHARACTERS;
        }
        return $this->parse($value, $region) === null ? self::INVALID_NUMBER : null;
    }

    public function normalize(mixed $value, string $region = 'SE'): mixed
    {
        $number = is_string($value) ? $this->parse($value, $region) : null;
        return $number === null ? $value : PhoneNumberUtil::getInstance()->format($number, PhoneNumberFormat::E164);
    }

    public function parse(string $value, string $region = 'SE'): ?PhoneNumber
    {
        if (preg_match('/\A\+?[0-9 ().-]+\z/', $value) !== 1) {
            return null;
        }
        $util = PhoneNumberUtil::getInstance();
        try {
            $number = $util->parse(str_starts_with($value, '00') ? '+' . substr($value, 2) : $value, $region);
            return $util->isValidNumber($number) ? $number : null;
        } catch (NumberParseException) {
            return null;
        }
    }
}
