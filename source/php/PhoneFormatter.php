<?php

declare(strict_types=1);

namespace AcfPhoneNumberField;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final class PhoneFormatter
{
    public function format(mixed $value, string $format = 'e164', string $region = 'SE'): mixed
    {
        $number = is_string($value) ? (new PhoneValidator())->parse($value, $region) : null;
        if ($number === null) {
            return $value;
        }
        $util = PhoneNumberUtil::getInstance();
        if ($format === 'array') {
            return [
                'e164' => $util->format($number, PhoneNumberFormat::E164),
                'national' => $util->format($number, PhoneNumberFormat::NATIONAL),
                'international' => $util->format($number, PhoneNumberFormat::INTERNATIONAL),
                'country_code' => $number->getCountryCode(),
                'region' => $util->getRegionCodeForNumber($number),
            ];
        }
        return $util->format($number, match ($format) {
            'national' => PhoneNumberFormat::NATIONAL,
            'international' => PhoneNumberFormat::INTERNATIONAL,
            default => PhoneNumberFormat::E164,
        });
    }

    public function display(mixed $value, string $format = 'auto', string $region = 'SE'): mixed
    {
        if ($format === 'auto') {
            $number = is_string($value) ? (new PhoneValidator())->parse($value, $region) : null;
            $format =
                $region === 'SE'
                && $number !== null
                && PhoneNumberUtil::getInstance()->getRegionCodeForNumber($number) === 'SE'
                    ? 'national'
                    : 'international';
        }
        return $this->format($value, $format, $region);
    }
}
