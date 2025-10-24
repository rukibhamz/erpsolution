<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TaxType extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'code',
        'description',
        'rate',
        'rate_type',
        'is_percentage',
        'is_active',
        'applies_to',
        'calculation_method',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_percentage' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'rate', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the tax calculations for this type.
     */
    public function taxCalculations(): HasMany
    {
        return $this->hasMany(TaxCalculation::class, 'tax_type_id');
    }

    /**
     * Get the tax payments for this type.
     */
    public function taxPayments(): HasMany
    {
        return $this->hasMany(TaxPayment::class, 'tax_type_id');
    }

    /**
     * Get the tax type badge color.
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->code) {
            'VAT' => 'blue',
            'AMAC' => 'green',
            'PAYE' => 'yellow',
            'WHT' => 'red',
            'CIT' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Check if tax is percentage based.
     */
    public function isPercentage(): bool
    {
        return $this->is_percentage;
    }

    /**
     * Check if tax is fixed amount.
     */
    public function isFixed(): bool
    {
        return !$this->is_percentage;
    }

    /**
     * Calculate tax amount for given base amount.
     */
    public function calculateTax(float $baseAmount): float
    {
        if ($this->getAttribute('is_percentage')) {
            return ($baseAmount * $this->rate) / 100;
        }
        
        return $this->rate;
    }

    /**
     * Get the formatted rate for display.
     */
    public function getFormattedRateAttribute(): string
    {
        if ($this->getAttribute('is_percentage')) {
            return $this->getAttribute('rate') . '%';
        }
        
        return '₦' . number_format((float) $this->getAttribute('rate'), 2);
    }

    /**
     * Scope for active tax types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for percentage tax types.
     */
    public function scopePercentage($query)
    {
        return $query->where('is_percentage', true);
    }

    /**
     * Scope for fixed tax types.
     */
    public function scopeFixed($query)
    {
        return $query->where('is_percentage', false);
    }

    /**
     * Scope for tax types by code.
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }
}
