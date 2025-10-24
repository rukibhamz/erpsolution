<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidNigerianPhone implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $value);
        
        // Check if phone number is valid Nigerian format
        if (!preg_match('/^(234|0)?[789][01][0-9]{8}$/', $phone)) {
            $fail('The :attribute must be a valid Nigerian phone number.');
        }
    }
}
