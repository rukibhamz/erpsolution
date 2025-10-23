<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EventBooking extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'booking_reference',
        'event_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'number_of_attendees',
        'total_amount',
        'deposit_amount',
        'balance_amount',
        'amount_paid',
        'payment_status',
        'booking_status',
        'special_requirements',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['booking_reference', 'customer_name', 'total_amount', 'payment_status', 'booking_status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the event for this booking.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user who created this booking.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the payments for this booking.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(BookingPayment::class, 'booking_id');
    }

    /**
     * Get the total amount paid.
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->payments()
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
     * Check if booking is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->booking_status === 'confirmed';
    }

    /**
     * Check if booking is pending.
     */
    public function isPending(): bool
    {
        return $this->booking_status === 'pending';
    }

    /**
     * Check if booking is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->booking_status === 'cancelled';
    }

    /**
     * Check if booking is completed.
     */
    public function isCompleted(): bool
    {
        return $this->booking_status === 'completed';
    }

    /**
     * Check if payment is complete.
     */
    public function isFullyPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if payment is partial.
     */
    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === 'partial';
    }

    /**
     * Check if payment is pending.
     */
    public function isPaymentPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * Get the booking status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->booking_status) {
            'pending' => 'yellow',
            'confirmed' => 'green',
            'cancelled' => 'red',
            'completed' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get the payment status badge color.
     */
    public function getPaymentStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            'pending' => 'yellow',
            'partial' => 'orange',
            'paid' => 'green',
            'refunded' => 'red',
            default => 'gray',
        };
    }

    /**
     * Mark booking as confirmed.
     */
    public function markAsConfirmed(): void
    {
        $this->update(['booking_status' => 'confirmed']);
    }

    /**
     * Mark booking as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update(['booking_status' => 'cancelled']);
    }

    /**
     * Mark booking as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update(['booking_status' => 'completed']);
    }

    /**
     * Update payment status.
     */
    public function updatePaymentStatus(): void
    {
        $totalPaid = $this->total_paid;
        
        if ($totalPaid >= $this->total_amount) {
            $this->update(['payment_status' => 'paid']);
        } elseif ($totalPaid > 0) {
            $this->update(['payment_status' => 'partial']);
        } else {
            $this->update(['payment_status' => 'pending']);
        }
    }

    /**
     * Scope for confirmed bookings.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('booking_status', 'confirmed');
    }

    /**
     * Scope for pending bookings.
     */
    public function scopePending($query)
    {
        return $query->where('booking_status', 'pending');
    }

    /**
     * Scope for paid bookings.
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope for bookings by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereHas('event', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate]);
        });
    }
}
