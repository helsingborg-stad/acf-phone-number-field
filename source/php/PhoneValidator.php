<?php

declare(strict_types=1);

namespace AcfPhoneNumberField;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\ValidationResult;

final class PhoneValidator
{
    public const UNSUPPORTED_CHARACTERS = 'unsupported_characters';
    public const INVALID_NUMBER = 'invalid_number';
    public const TOO_SHORT = 'too_short';
    public const TOO_LONG = 'too_long';

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
        $util = PhoneNumberUtil::getInstance();
        try {
            $number = $this->parseNumber($value, $region);
        } catch (NumberParseException $exception) {
            return match ($exception->getErrorType()) {
                NumberParseException::TOO_SHORT_AFTER_IDD, NumberParseException::TOO_SHORT_NSN => self::TOO_SHORT,
                NumberParseException::TOO_LONG => self::TOO_LONG,
                default => self::INVALID_NUMBER,
            };
        }
        if ($util->isValidNumber($number)) {
            return null;
        }
        return match ($util->isPossibleNumberWithReason($number)) {
            ValidationResult::TOO_SHORT => self::TOO_SHORT,
            ValidationResult::TOO_LONG => self::TOO_LONG,
            default => self::INVALID_NUMBER,
        };
    }

    public function normalize(mixed $value, string $region = 'SE'): mixed
    {
        $number = is_string($value) ? $this->parse($value, $region) : null;
        return $number === null ? $value : PhoneNumberUtil::getInstance()->format($number, PhoneNumberFormat::E164);
    }

    private function parseNumber(string $value, string $region): PhoneNumber
    {
        return PhoneNumberUtil::getInstance()->parse(
            str_starts_with($value, '00') ? '+' . substr($value, 2) : $value,
            $region,
        );
    }

    public function parse(string $value, string $region = 'SE'): ?PhoneNumber
    {
        if (preg_match('/\A\+?[0-9 ().-]+\z/', $value) !== 1) {
            return null;
        }
        $util = PhoneNumberUtil::getInstance();
        try {
            $number = $this->parseNumber($value, $region);
            return $util->isValidNumber($number) ? $number : null;
        } catch (NumberParseException) {
            return null;
        }
    }
}
