<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidNigerianBankAccount implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Remove any spaces or dashes
        $accountNumber = preg_replace('/[\s\-]/', '', $value);
        
        // Check if it's a valid Nigerian bank account number
        if (!$this->isValidNigerianBankAccount($accountNumber)) {
            $fail('The :attribute must be a valid Nigerian bank account number.');
        }
    }

    /**
     * Validate Nigerian bank account number
     */
    private function isValidNigerianBankAccount(string $accountNumber): bool
    {
        // Nigerian bank account numbers are typically 10 digits
        if (!preg_match('/^\d{10}$/', $accountNumber)) {
            return false;
        }

        // Additional validation for Nigerian bank account format
        // Most Nigerian banks use 10-digit account numbers
        $firstDigit = (int) $accountNumber[0];
        
        // Some banks have specific first digit patterns
        $validFirstDigits = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        
        if (!in_array($firstDigit, $validFirstDigits)) {
            return false;
        }

        // Check for obviously invalid patterns
        if (preg_match('/^(\d)\1{9}$/', $accountNumber)) {
            return false; // All same digits
        }

        return true;
    }
}
