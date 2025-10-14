<?php
// app/Services/PhoneValidator.php

namespace App\Services;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneValidator
{
    private PhoneNumberUtil $phoneUtil;

    public function __construct()
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
    }

    /**
     * Validate and normalize phone number
     * Returns normalized phone without country code or throws exception
     */
    public function validateAndNormalize(string $countryCode, string $phone): array
    {
        // Remove any spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        // Check if user included country code in phone field
        if (str_starts_with($phone, $countryCode)) {
            throw new \InvalidArgumentException('Please enter phone number without country code.');
        }

        // Remove leading zeros
        $phone = ltrim($phone, '0');

        // Get country code from the prefix (e.g., +20 -> EG)
        try {
            // Try to parse with the country code
            $fullNumber = $countryCode . $phone;
            $phoneNumber = $this->phoneUtil->parse($fullNumber, null);

            // Validate the number
            if (!$this->phoneUtil->isValidNumber($phoneNumber)) {
                throw new \InvalidArgumentException('Invalid phone number for the selected country.');
            }

            // Get normalized format
            $nationalNumber = $phoneNumber->getNationalNumber();
            $e164Format = $this->phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);

            return [
                'country_code' => $countryCode,
                'phone' => (string)$nationalNumber,
                'full_phone' => $e164Format,
                'is_valid' => true,
            ];

        } catch (NumberParseException $e) {
            throw new \InvalidArgumentException('Invalid phone number format.');
        }
    }

    /**
     * Get country code from region (e.g., EG -> +20)
     */
    public function getCountryCodeFromRegion(string $region): string
    {
        try {
            $countryCode = $this->phoneUtil->getCountryCodeForRegion(strtoupper($region));
            return '+' . $countryCode;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid country region.');
        }
    }

    /**
     * Check if phone starts with country code and strip it
     */
    public function stripCountryCode(string $countryCode, string $phone): string
    {
        $cleanCountryCode = ltrim($countryCode, '+');
        $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $phone);

        // Check if phone starts with country code (with or without +)
        if (str_starts_with($cleanPhone, '+' . $cleanCountryCode)) {
            return substr($cleanPhone, strlen($cleanCountryCode) + 1);
        }

        if (str_starts_with($cleanPhone, $cleanCountryCode)) {
            return substr($cleanPhone, strlen($cleanCountryCode));
        }

        return $cleanPhone;
    }
}
