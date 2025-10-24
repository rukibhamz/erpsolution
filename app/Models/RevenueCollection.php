<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RevenueCollection extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'collection_reference',
        'property_id',
        'collection_type',
        'amount',
        'collection_date',
        'due_date',
        'paid_date',
        'payment_status',
        'payment_method',
        'reference_number',
        'description',
        'notes',
        'collected_by',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'collection_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['collection_reference', 'amount', 'payment_status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the property for this collection.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Get the user who collected this revenue.
     */
    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    /**
     * Get the user who verified this collection.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the revenue collection items for this collection.
     */
    public function revenueCollectionItems(): HasMany
    {
        return $this->hasMany(RevenueCollectionItem::class, 'revenue_collection_id');
    }

    /**
     * Get the payment status badge color.
     */
    public function getPaymentStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            'pending' => 'yellow',
            'paid' => 'green',
            'overdue' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get the collection type badge color.
     */
    public function getCollectionTypeColorAttribute(): string
    {
        return match ($this->collection_type) {
            'rent' => 'blue',
            'service_charge' => 'green',
            'maintenance_fee' => 'yellow',
            'penalty' => 'red',
            'other' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Check if collection is pending.
     */
    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * Check if collection is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if collection is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->payment_status === 'overdue';
    }

    /**
     * Check if collection is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->payment_status === 'cancelled';
    }

    /**
     * Check if collection is verified.
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Get the formatted amount for display.
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₦' . number_format((float) $this->amount, 2);
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
     * Mark collection as paid.
     */
    public function markAsPaid(): void
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_date' => now(),
        ]);
    }

    /**
     * Mark collection as overdue.
     */
    public function markAsOverdue(): void
    {
        $this->update(['payment_status' => 'overdue']);
    }

    /**
     * Mark collection as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update(['payment_status' => 'cancelled']);
    }

    /**
     * Verify collection.
     */
    public function verify(User $user): void
    {
        $this->update([
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);
    }

    /**
     * Scope for pending collections.
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    /**
     * Scope for paid collections.
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope for overdue collections.
     */
    public function scopeOverdue($query)
    {
        return $query->where('payment_status', 'overdue');
    }

    /**
     * Scope for collections by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('collection_date', [$startDate, $endDate]);
    }

    /**
     * Scope for collections by type.
     */
    public function scopeByType($query, $collectionType)
    {
        return $query->where('collection_type', $collectionType);
    }

    /**
     * Scope for verified collections.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Scope for unverified collections.
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('verified_at');
    }
}
