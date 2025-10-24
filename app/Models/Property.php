<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Property extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * SECURITY FIX: Restricted fillable fields to prevent mass assignment
     */
    protected $fillable = [
        'name',
        'property_code',
        'description',
        'property_type_id',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'rent_amount',
        'deposit_amount',
        'bedrooms',
        'bathrooms',
        'area_sqft',
        'amenities',
        'images',
        'status',
        'is_active',
    ];

    protected $casts = [
        'rent_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'area_sqft' => 'decimal:2',
        'amenities' => 'array',
        'images' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'property_code', 'rent_amount', 'status', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the property type.
     */
    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class);
    }

    /**
     * Get the leases for this property.
     */
    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    /**
     * Get the current lease.
     * BUSINESS LOGIC FIX: Properly check for active leases
     */
    public function currentLease()
    {
        return $this->leases()
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
    }

    /**
     * Get the property's full address.
     */
    public function getFullAddressAttribute(): string
    {
        return implode(', ', array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]));
    }

    /**
     * Get the property's status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'available' => 'green',
            'occupied' => 'blue',
            'maintenance' => 'yellow',
            'unavailable' => 'red',
            default => 'gray',
        };
    }

    /**
     * BUSINESS LOGIC FIX: Check if property is truly available
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available' 
            && $this->is_active 
            && !$this->currentLease(); // Check for active leases
    }

    /**
     * Check if property is occupied.
     */
    public function isOccupied(): bool
    {
        return $this->status === 'occupied' || $this->currentLease() !== null;
    }

    /**
     * Scope for available properties.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
            ->where('is_active', true)
            ->whereDoesntHave('leases', function ($q) {
                $q->where('status', 'active')
                  ->where('start_date', '<=', now())
                  ->where('end_date', '>=', now());
            });
    }

    /**
     * Scope for occupied properties.
     */
    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied')
            ->orWhereHas('leases', function ($q) {
                $q->where('status', 'active')
                  ->where('start_date', '<=', now())
                  ->where('end_date', '>=', now());
            });
    }

    /**
     * Scope for active properties.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get formatted rent amount.
     */
    public function getFormattedRentAmountAttribute(): string
    {
        return '₦' . number_format($this->rent_amount, 2);
    }

    /**
     * Get formatted deposit amount.
     */
    public function getFormattedDepositAmountAttribute(): string
    {
        return '₦' . number_format($this->deposit_amount, 2);
    }
}