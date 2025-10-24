<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidNigerianAmount implements ValidationRule
{
    /**
     * The maximum amount allowed.
     */
    protected float $maxAmount;

    /**
     * Create a new rule instance.
     */
    public function __construct(float $maxAmount = 999999999.99)
    {
        $this->maxAmount = $maxAmount;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail('The :attribute must be a valid number.');
            return;
        }

        $amount = (float) $value;

        if ($amount < 0) {
            $fail('The :attribute cannot be negative.');
            return;
        }

        if ($amount > $this->maxAmount) {
            $fail('The :attribute cannot exceed ₦' . number_format($this->maxAmount, 2));
            return;
        }

        // Check for reasonable decimal places (max 2)
        if (strpos($value, '.') !== false) {
            $decimalPlaces = strlen(substr($value, strpos($value, '.') + 1));
            if ($decimalPlaces > 2) {
                $fail('The :attribute cannot have more than 2 decimal places.');
            }
        }
    }
}
