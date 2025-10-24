<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\Property;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaseManagementService
{
    /**
     * Create lease with proper business logic validation
     */
    public function createLease(array $data): array
    {
        $errors = [];
        $warnings = [];

        // Get property
        $property = Property::find($data['property_id']);
        if (!$property) {
            $errors[] = 'Property not found.';
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        // Validate property availability
        if (!$property->isAvailable()) {
            $errors[] = 'Property is not available for lease.';
        }

        // Check for overlapping leases
        $overlappingLease = Lease::where('property_id', $property->id)
            ->where('status', 'active')
            ->where(function ($query) use ($data) {
                $query->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                      ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']])
                      ->orWhere(function ($q) use ($data) {
                          $q->where('start_date', '<=', $data['start_date'])
                            ->where('end_date', '>=', $data['end_date']);
                      });
            })
            ->first();

        if ($overlappingLease) {
            $errors[] = 'Property has overlapping lease from ' . $overlappingLease->start_date . ' to ' . $overlappingLease->end_date . '.';
        }

        // Validate lease dates
        if ($data['start_date'] < now()->toDateString()) {
            $errors[] = 'Lease start date cannot be in the past.';
        }

        if ($data['end_date'] <= $data['start_date']) {
            $errors[] = 'Lease end date must be after start date.';
        }

        // Validate lease duration (minimum 1 month)
        $startDate = \Carbon\Carbon::parse($data['start_date']);
        $endDate = \Carbon\Carbon::parse($data['end_date']);
        $durationInMonths = $startDate->diffInMonths($endDate);

        if ($durationInMonths < 1) {
            $errors[] = 'Lease duration must be at least 1 month.';
        }

        // Validate rent amount
        if ($data['monthly_rent'] <= 0) {
            $errors[] = 'Monthly rent must be greater than zero.';
        }

        if ($data['security_deposit'] < 0) {
            $errors[] = 'Security deposit cannot be negative.';
        }

        // Check if rent amount is reasonable (not more than 10x property rent)
        if ($property->rent_amount > 0 && $data['monthly_rent'] > ($property->rent_amount * 10)) {
            $warnings[] = 'Monthly rent is significantly higher than property base rent.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        try {
            DB::transaction(function () use ($data, $property) {
                // Create lease
                $lease = Lease::create($data);

                // Update property status to occupied
                $property->update(['status' => 'occupied']);

                Log::info("Lease {$lease->id} created for property {$property->id}");
            });

            return ['success' => true, 'errors' => [], 'warnings' => $warnings];

        } catch (\Exception $e) {
            Log::error("Failed to create lease: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to create lease: ' . $e->getMessage()], 'warnings' => $warnings];
        }
    }

    /**
     * Terminate lease with proper business logic
     */
    public function terminateLease(Lease $lease, string $reason = null): array
    {
        $errors = [];

        // Validate lease can be terminated
        if ($lease->status !== 'active') {
            $errors[] = 'Only active leases can be terminated.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            DB::transaction(function () use ($lease, $reason) {
                // Update lease status
                $lease->update([
                    'status' => 'terminated',
                    'notes' => $lease->notes . ($reason ? "\nTermination reason: " . $reason : ''),
                ]);

                // Update property status to available
                $lease->property->update(['status' => 'available']);

                Log::info("Lease {$lease->id} terminated");
            });

            return ['success' => true, 'errors' => []];

        } catch (\Exception $e) {
            Log::error("Failed to terminate lease {$lease->id}: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to terminate lease: ' . $e->getMessage()]];
        }
    }

    /**
     * Check lease expiry and update status
     */
    public function checkLeaseExpiry(): array
    {
        $expiredLeases = Lease::where('status', 'active')
            ->where('end_date', '<', now())
            ->get();

        $updated = [];

        foreach ($expiredLeases as $lease) {
            try {
                DB::transaction(function () use ($lease) {
                    // Update lease status
                    $lease->update(['status' => 'expired']);

                    // Update property status to available
                    $lease->property->update(['status' => 'available']);

                    Log::info("Lease {$lease->id} expired and property {$lease->property_id} made available");
                });

                $updated[] = $lease->id;

            } catch (\Exception $e) {
                Log::error("Failed to expire lease {$lease->id}: " . $e->getMessage());
            }
        }

        return $updated;
    }

    /**
     * Get lease summary
     */
    public function getLeaseSummary(Lease $lease): array
    {
        $daysRemaining = now()->diffInDays($lease->end_date, false);
        $isExpired = $lease->end_date < now();
        $isExpiringSoon = $daysRemaining <= 30 && $daysRemaining > 0;

        return [
            'lease' => $lease,
            'property' => $lease->property,
            'days_remaining' => $daysRemaining,
            'is_expired' => $isExpired,
            'is_expiring_soon' => $isExpiringSoon,
            'status' => $lease->status,
            'can_terminate' => $lease->status === 'active',
            'can_renew' => $lease->status === 'active' && $isExpiringSoon,
        ];
    }

    /**
     * Validate lease renewal
     */
    public function validateLeaseRenewal(Lease $lease, array $renewalData): array
    {
        $errors = [];

        // Check if lease can be renewed
        if ($lease->status !== 'active') {
            $errors[] = 'Only active leases can be renewed.';
        }

        // Check if lease is expiring soon (within 30 days)
        $daysRemaining = now()->diffInDays($lease->end_date, false);
        if ($daysRemaining > 30) {
            $errors[] = 'Lease can only be renewed within 30 days of expiry.';
        }

        // Validate new end date
        if ($renewalData['new_end_date'] <= $lease->end_date) {
            $errors[] = 'New end date must be after current end date.';
        }

        // Validate new rent amount
        if (isset($renewalData['new_monthly_rent']) && $renewalData['new_monthly_rent'] <= 0) {
            $errors[] = 'New monthly rent must be greater than zero.';
        }

        return $errors;
    }
}
