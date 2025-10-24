<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JournalEntry extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'entry_reference',
        'description',
        'entry_date',
        'created_by',
        'approved_by',
        'approved_at',
        'status',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['entry_reference', 'status', 'entry_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the user who created this entry.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this entry.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the journal entry items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class);
    }

    /**
     * Get the entry status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'posted' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    /**
     * Check if entry is posted.
     */
    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    /**
     * Check if entry is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if entry is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->getAttribute('status') === 'cancelled';
    }

    /**
     * Get total debit amount.
     */
    public function getTotalDebitAttribute(): float
    {
        return $this->items()->sum('debit_amount');
    }

    /**
     * Get total credit amount.
     */
    public function getTotalCreditAttribute(): float
    {
        return $this->items()->sum('credit_amount');
    }

    /**
     * Check if entry is balanced.
     */
    public function isBalanced(): bool
    {
        return abs($this->getAttribute('total_debit') - $this->getAttribute('total_credit')) < 0.01;
    }

    /**
     * Scope for posted entries.
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    /**
     * Scope for draft entries.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for entries by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('entry_date', [$startDate, $endDate]);
    }
}