<?php

namespace App\Rules;

use App\Models\Lease;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoLeaseOverlap implements ValidationRule
{
    protected $propertyId;
    protected $excludeLeaseId;

    public function __construct($propertyId, $excludeLeaseId = null)
    {
        $this->propertyId = $propertyId;
        $this->excludeLeaseId = $excludeLeaseId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $startDate = request()->input('start_date');
        $endDate = $value;

        if (!$startDate || !$endDate) {
            return; // Let other validation rules handle required fields
        }

        // Check for overlapping leases
        $query = Lease::where('property_id', $this->propertyId)
            ->where('status', 'active')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($subQ) use ($startDate, $endDate) {
                      $subQ->where('start_date', '<=', $startDate)
                           ->where('end_date', '>=', $endDate);
                  });
            });

        // Exclude current lease when updating
        if ($this->excludeLeaseId) {
            $query->where('id', '!=', $this->excludeLeaseId);
        }

        if ($query->exists()) {
            $fail('The selected dates overlap with an existing lease for this property.');
        }
    }
}
