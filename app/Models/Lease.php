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
        'lease_reference',
        'property_id',
        'tenant_name',
        'tenant_email',
        'tenant_phone',
        'tenant_address',
        'start_date',
        'end_date',
        'monthly_rent',
        'security_deposit',
        'late_fee',
        'grace_period_days',
        'status',
        'terms_conditions',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_rent' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'late_fee' => 'decimal:2',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['lease_reference', 'status', 'monthly_rent', 'start_date', 'end_date'])
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
     * Get the payments for this lease.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(LeasePayment::class);
    }

    /**
     * Get the lease status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'active' => 'green',
            'expired' => 'red',
            'terminated' => 'orange',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    /**
     * Check if lease is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if lease is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->end_date < now();
    }

    /**
     * Check if lease is terminated.
     */
    public function isTerminated(): bool
    {
        return $this->status === 'terminated';
    }

    /**
     * Check if lease is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get formatted monthly rent.
     */
    public function getFormattedMonthlyRentAttribute(): string
    {
        return '₦' . number_format((float) $this->monthly_rent, 2);
    }

    /**
     * Get formatted security deposit.
     */
    public function getFormattedSecurityDepositAttribute(): string
    {
        return '₦' . number_format((float) $this->security_deposit, 2);
    }

    /**
     * Get formatted late fee.
     */
    public function getFormattedLateFeeAttribute(): string
    {
        return '₦' . number_format((float) $this->late_fee, 2);
    }

    /**
     * Scope for active leases.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for expired leases.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere('end_date', '<', now());
    }

    /**
     * Scope for leases by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }
}