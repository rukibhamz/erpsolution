<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidNigerianPostalCode implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->isValidNigerianPostalCode($value)) {
            $fail('The :attribute must be a valid Nigerian postal code.');
        }
    }

    /**
     * Validate Nigerian postal code
     */
    private function isValidNigerianPostalCode(string $postalCode): bool
    {
        // Nigerian postal codes are 6 digits
        if (!preg_match('/^\d{6}$/', $postalCode)) {
            return false;
        }

        // Check for valid Nigerian postal code ranges
        $firstDigit = (int) $postalCode[0];
        
        // Nigerian postal codes typically start with 1-9
        if ($firstDigit < 1 || $firstDigit > 9) {
            return false;
        }

        // Check for obviously invalid patterns
        if (preg_match('/^(\d)\1{5}$/', $postalCode)) {
            return false; // All same digits
        }

        return true;
    }
}
