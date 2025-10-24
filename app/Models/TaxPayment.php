<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TaxPayment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'tax_calculation_id',
        'tax_type_id',
        'payment_reference',
        'amount',
        'payment_date',
        'payment_method',
        'payment_status',
        'reference_number',
        'notes',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['payment_reference', 'amount', 'payment_status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the tax calculation for this payment.
     */
    public function taxCalculation(): BelongsTo
    {
        return $this->belongsTo(TaxCalculation::class, 'tax_calculation_id');
    }

    /**
     * Get the tax type for this payment.
     */
    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class, 'tax_type_id');
    }

    /**
     * Get the user who processed this payment.
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the payment status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            'pending' => 'yellow',
            'completed' => 'green',
            'failed' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->payment_status === 'completed';
    }

    /**
     * Check if payment failed.
     */
    public function isFailed(): bool
    {
        return $this->payment_status === 'failed';
    }

    /**
     * Check if payment is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->payment_status === 'cancelled';
    }

    /**
     * Get the formatted amount for display.
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₦' . number_format((float) $this->amount, 2);
    }

    /**
     * Mark payment as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update(['payment_status' => 'completed']);
        
        // Update tax calculation status
        $this->taxCalculation->markAsPaid();
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(): void
    {
        $this->update(['payment_status' => 'failed']);
    }

    /**
     * Mark payment as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update(['payment_status' => 'cancelled']);
    }

    /**
     * Scope for pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    /**
     * Scope for completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('payment_status', 'completed');
    }

    /**
     * Scope for failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }

    /**
     * Scope for payments by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope for payments by tax type.
     */
    public function scopeByTaxType($query, $taxTypeId)
    {
        return $query->where('tax_type_id', $taxTypeId);
    }
}
