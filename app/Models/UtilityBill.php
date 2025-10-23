<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UtilityBill extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'bill_number',
        'property_id',
        'utility_type_id',
        'meter_id',
        'billing_period_start',
        'billing_period_end',
        'previous_reading',
        'current_reading',
        'consumption',
        'rate_per_unit',
        'base_amount',
        'tax_amount',
        'total_amount',
        'due_date',
        'paid_date',
        'payment_status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'previous_reading' => 'decimal:2',
        'current_reading' => 'decimal:2',
        'consumption' => 'decimal:2',
        'rate_per_unit' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['bill_number', 'consumption', 'total_amount', 'payment_status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the property for this bill.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Get the utility type for this bill.
     */
    public function utilityType(): BelongsTo
    {
        return $this->belongsTo(UtilityType::class, 'utility_type_id');
    }

    /**
     * Get the meter for this bill.
     */
    public function meter(): BelongsTo
    {
        return $this->belongsTo(UtilityMeter::class, 'meter_id');
    }

    /**
     * Get the user who created this bill.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the bill payments for this bill.
     */
    public function billPayments(): HasMany
    {
        return $this->hasMany(UtilityBillPayment::class, 'bill_id');
    }

    /**
     * Get the payment status badge color.
     */
    public function getPaymentStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'green',
            'pending' => 'yellow',
            'overdue' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Check if bill is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if bill is pending.
     */
    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * Check if bill is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->payment_status === 'overdue';
    }

    /**
     * Check if bill is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->payment_status === 'cancelled';
    }

    /**
     * Get the formatted total amount for display.
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return '₦' . number_format($this->total_amount, 2);
    }

    /**
     * Get the formatted consumption for display.
     */
    public function getFormattedConsumptionAttribute(): string
    {
        return number_format($this->consumption, 2) . ' ' . $this->utilityType->unit_of_measure;
    }

    /**
     * Get the days until due date.
     */
    public function getDaysUntilDueAttribute(): int
    {
        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Get the days overdue.
     */
    public function getDaysOverdueAttribute(): int
    {
        if ($this->due_date >= now()) {
            return 0;
        }
        
        return now()->diffInDays($this->due_date);
    }

    /**
     * Get the total amount paid.
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->billPayments()
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get the outstanding balance.
     */
    public function getOutstandingBalanceAttribute(): float
    {
        return $this->total_amount - $this->total_paid;
    }

    /**
     * Mark bill as paid.
     */
    public function markAsPaid(): void
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_date' => now(),
        ]);
    }

    /**
     * Mark bill as overdue.
     */
    public function markAsOverdue(): void
    {
        $this->update(['payment_status' => 'overdue']);
    }

    /**
     * Mark bill as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update(['payment_status' => 'cancelled']);
    }

    /**
     * Calculate bill amounts.
     */
    public function calculateAmounts(): void
    {
        $this->consumption = $this->current_reading - $this->previous_reading;
        $this->base_amount = $this->consumption * $this->rate_per_unit;
        $this->tax_amount = $this->base_amount * 0.05; // 5% tax
        $this->total_amount = $this->base_amount + $this->tax_amount;
    }

    /**
     * Scope for paid bills.
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope for pending bills.
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    /**
     * Scope for overdue bills.
     */
    public function scopeOverdue($query)
    {
        return $query->where('payment_status', 'overdue');
    }

    /**
     * Scope for bills by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('billing_period_start', [$startDate, $endDate]);
    }

    /**
     * Scope for bills by utility type.
     */
    public function scopeByUtilityType($query, $utilityTypeId)
    {
        return $query->where('utility_type_id', $utilityTypeId);
    }
}
