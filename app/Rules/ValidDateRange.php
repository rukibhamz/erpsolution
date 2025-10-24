<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class ValidDateRange implements ValidationRule
{
    protected $startDate;
    protected $endDate;
    protected $maxDays;

    /**
     * Create a new rule instance.
     */
    public function __construct($startDate, $endDate, $maxDays = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->maxDays = $maxDays;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->isValidDateRange($value)) {
            $fail('The :attribute must be within a valid date range.');
        }
    }

    /**
     * Validate date range
     */
    private function isValidDateRange($value): bool
    {
        try {
            $date = Carbon::parse($value);
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);

            // Check if date is within range
            if ($date->lt($start) || $date->gt($end)) {
                return false;
            }

            // Check maximum days if specified
            if ($this->maxDays && $start->diffInDays($end) > $this->maxDays) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
