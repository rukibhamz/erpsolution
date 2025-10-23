<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Event extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'venue',
        'venue_address',
        'start_date',
        'end_date',
        'max_attendees',
        'price_per_person',
        'deposit_amount',
        'deposit_percentage',
        'terms_and_conditions',
        'images',
        'amenities',
        'status',
        'is_public',
        'allow_partial_payment',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'price_per_person' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'images' => 'array',
        'amenities' => 'array',
        'is_public' => 'boolean',
        'allow_partial_payment' => 'boolean',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'start_date', 'end_date', 'price_per_person', 'status', 'is_public'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the category for this event.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    /**
     * Get the bookings for this event.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(EventBooking::class);
    }

    /**
     * Get the total number of attendees.
     */
    public function getTotalAttendeesAttribute(): int
    {
        return $this->bookings()->sum('number_of_attendees');
    }

    /**
     * Get the remaining capacity.
     */
    public function getRemainingCapacityAttribute(): int
    {
        return $this->max_attendees - $this->total_attendees;
    }

    /**
     * Get the total revenue from bookings.
     */
    public function getTotalRevenueAttribute(): float
    {
        return $this->bookings()
            ->where('payment_status', 'paid')
            ->sum('amount_paid');
    }

    /**
     * Check if event is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if event is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->start_date > now();
    }

    /**
     * Check if event is ongoing.
     */
    public function isOngoing(): bool
    {
        return $this->start_date <= now() && $this->end_date >= now();
    }

    /**
     * Check if event is completed.
     */
    public function isCompleted(): bool
    {
        return $this->end_date < now();
    }

    /**
     * Check if event is fully booked.
     */
    public function isFullyBooked(): bool
    {
        return $this->total_attendees >= $this->max_attendees;
    }

    /**
     * Get the event duration in hours.
     */
    public function getDurationInHoursAttribute(): float
    {
        return $this->start_date->diffInHours($this->end_date);
    }

    /**
     * Get the event status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'published' => 'green',
            'cancelled' => 'red',
            'completed' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Scope for published events.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    /**
     * Scope for public events.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for events by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }
}
