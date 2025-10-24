<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Lease;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PropertyStatusService
{
    /**
     * Update property status based on lease status
     */
    public function updatePropertyStatus(Property $property): void
    {
        DB::transaction(function () use ($property) {
            // Check for active leases
            $activeLease = $property->leases()
                ->where('status', 'active')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->first();

            if ($activeLease) {
                // Property has active lease
                if ($property->status !== 'occupied') {
                    $property->update(['status' => 'occupied']);
                    Log::info("Property {$property->getKey()} status updated to occupied due to active lease");
                }
            } else {
                // No active lease
                if ($property->status === 'occupied') {
                    $property->update(['status' => 'available']);
                    Log::info("Property {$property->getKey()} status updated to available - no active lease");
                }
            }
        });
    }

    /**
     * Check if property can be leased
     */
    public function canBeLeased(Property $property): bool
    {
        // Property must be available
        if ($property->status !== 'available') {
            return false;
        }

        // Property must be active
        if (!$property->is_active) {
            return false;
        }

        // Check for any active leases
        $hasActiveLease = $property->leases()
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->exists();

        return !$hasActiveLease;
    }

    /**
     * Validate property status change
     */
    public function validateStatusChange(Property $property, string $newStatus): array
    {
        $errors = [];

        switch ($newStatus) {
            case 'occupied':
                // Check if property has active lease
                $activeLease = $property->leases()
                    ->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->first();

                if (!$activeLease) {
                    $errors[] = 'Property cannot be marked as occupied without an active lease.';
                }
                break;

            case 'available':
                // Check if property has active lease
                $activeLease = $property->leases()
                    ->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->first();

                if ($activeLease) {
                    $errors[] = 'Property cannot be marked as available while it has an active lease.';
                }
                break;

            case 'maintenance':
                // Check if property has active lease
                $activeLease = $property->leases()
                    ->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->first();

                if ($activeLease) {
                    $errors[] = 'Property cannot be marked for maintenance while it has an active lease.';
                }
                break;
        }

        return $errors;
    }

    /**
     * Get property status summary
     */
    public function getStatusSummary(Property $property): array
    {
        $activeLease = $property->leases()
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        $upcomingLease = $property->leases()
            ->where('status', 'active')
            ->where('start_date', '>', now())
            ->first();

        $expiredLease = $property->leases()
            ->where('status', 'active')
            ->where('end_date', '<', now())
            ->first();

        return [
            'current_status' => $property->status,
            'has_active_lease' => $activeLease !== null,
            'active_lease' => $activeLease,
            'upcoming_lease' => $upcomingLease,
            'expired_lease' => $expiredLease,
            'can_be_leased' => $this->canBeLeased($property),
            'status_consistency' => $this->isStatusConsistent($property),
        ];
    }

    /**
     * Check if property status is consistent with lease data
     */
    public function isStatusConsistent(Property $property): bool
    {
        $activeLease = $property->leases()
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->exists();

        if ($property->status === 'occupied' && !$activeLease) {
            return false;
        }

        if ($property->status === 'available' && $activeLease) {
            return false;
        }

        return true;
    }

    /**
     * Fix property status inconsistencies
     */
    public function fixStatusInconsistencies(): array
    {
        $fixed = [];
        $properties = Property::all();

        foreach ($properties as $property) {
            if (!$this->isStatusConsistent($property)) {
                $this->updatePropertyStatus($property);
                $fixed[] = $property->id;
            }
        }

        return $fixed;
    }
}
