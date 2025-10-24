<?php

namespace App\Rules;

use App\Models\Lease;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoOverlappingLease implements ValidationRule
{
    /**
     * The property ID to check.
     */
    protected int $propertyId;

    /**
     * The lease ID to exclude (for updates).
     */
    protected ?int $excludeLeaseId;

    /**
     * Create a new rule instance.
     */
    public function __construct(int $propertyId, ?int $excludeLeaseId = null)
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
            return; // Let other validation rules handle missing dates
        }

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

        if ($this->excludeLeaseId) {
            $query->where('id', '!=', $this->excludeLeaseId);
        }

        $overlappingLease = $query->first();

        if ($overlappingLease) {
            $fail('The selected date range overlaps with an existing lease (ID: ' . $overlappingLease->id . ').');
        }
    }
}
