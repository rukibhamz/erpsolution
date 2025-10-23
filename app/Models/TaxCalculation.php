<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TaxCalculation extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'tax_type_id',
        'taxable_type',
        'taxable_id',
        'base_amount',
        'tax_rate',
        'tax_amount',
        'calculation_date',
        'period_start',
        'period_end',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'calculation_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tax_type_id', 'base_amount', 'tax_amount', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the tax type for this calculation.
     */
    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class, 'tax_type_id');
    }

    /**
     * Get the user who created this calculation.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the taxable entity (polymorphic relationship).
     */
    public function taxable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the tax calculation status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'calculated' => 'blue',
            'paid' => 'green',
            'overdue' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Check if calculation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if calculation is calculated.
     */
    public function isCalculated(): bool
    {
        return $this->status === 'calculated';
    }

    /**
     * Check if calculation is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if calculation is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'overdue';
    }

    /**
     * Check if calculation is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get the formatted base amount for display.
     */
    public function getFormattedBaseAmountAttribute(): string
    {
        return '₦' . number_format($this->base_amount, 2);
    }

    /**
     * Get the formatted tax amount for display.
     */
    public function getFormattedTaxAmountAttribute(): string
    {
        return '₦' . number_format($this->tax_amount, 2);
    }

    /**
     * Get the formatted tax rate for display.
     */
    public function getFormattedTaxRateAttribute(): string
    {
        return $this->tax_rate . '%';
    }

    /**
     * Calculate tax amount based on base amount and tax rate.
     */
    public function calculateTaxAmount(): void
    {
        $this->tax_amount = ($this->base_amount * $this->tax_rate) / 100;
    }

    /**
     * Mark calculation as calculated.
     */
    public function markAsCalculated(): void
    {
        $this->update(['status' => 'calculated']);
    }

    /**
     * Mark calculation as paid.
     */
    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid']);
    }

    /**
     * Mark calculation as overdue.
     */
    public function markAsOverdue(): void
    {
        $this->update(['status' => 'overdue']);
    }

    /**
     * Mark calculation as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Scope for pending calculations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for calculated taxes.
     */
    public function scopeCalculated($query)
    {
        return $query->where('status', 'calculated');
    }

    /**
     * Scope for paid taxes.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for overdue taxes.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    /**
     * Scope for calculations by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('calculation_date', [$startDate, $endDate]);
    }

    /**
     * Scope for calculations by tax type.
     */
    public function scopeByTaxType($query, $taxTypeId)
    {
        return $query->where('tax_type_id', $taxTypeId);
    }
}
