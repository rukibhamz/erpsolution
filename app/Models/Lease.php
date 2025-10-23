<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Lease extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'lease_number',
        'property_id',
        'tenant_id',
        'start_date',
        'end_date',
        'monthly_rent',
        'deposit_amount',
        'late_fee_amount',
        'late_fee_days',
        'rent_due_date',
        'terms_and_conditions',
        'additional_charges',
        'status',
        'termination_date',
        'termination_reason',
        'auto_renewal',
        'renewal_notice_days',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_rent' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'late_fee_amount' => 'decimal:2',
        'rent_due_date' => 'date',
        'additional_charges' => 'array',
        'termination_date' => 'date',
        'auto_renewal' => 'boolean',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['lease_number', 'monthly_rent', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the property for this lease.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the tenant for this lease.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the payments for this lease.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(LeasePayment::class);
    }

    /**
     * Get the total amount paid.
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->payments()
            ->where('status', 'completed')
            ->sum('total_amount');
    }

    /**
     * Get the outstanding balance.
     */
    public function getOutstandingBalanceAttribute(): float
    {
        $totalRent = $this->calculateTotalRent();
        return $totalRent - $this->total_paid;
    }

    /**
     * Calculate total rent for the lease period.
     */
    public function calculateTotalRent(): float
    {
        $months = $this->start_date->diffInMonths($this->end_date);
        return $months * $this->monthly_rent;
    }

    /**
     * Check if lease is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' 
            && $this->start_date <= now() 
            && $this->end_date >= now();
    }

    /**
     * Check if lease is expired.
     */
    public function isExpired(): bool
    {
        return $this->end_date < now();
    }

    /**
     * Check if lease is expiring soon.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->end_date <= now()->addDays($days);
    }

    /**
     * Get the lease duration in months.
     */
    public function getDurationInMonthsAttribute(): int
    {
        return $this->start_date->diffInMonths($this->end_date);
    }

    /**
     * Get the lease status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'green',
            'expired' => 'red',
            'terminated' => 'red',
            'renewed' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Scope for active leases.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope for expired leases.
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    /**
     * Scope for expiring leases.
     */
    public function scopeExpiring($query, int $days = 30)
    {
        return $query->where('end_date', '<=', now()->addDays($days))
            ->where('end_date', '>', now());
    }
}
