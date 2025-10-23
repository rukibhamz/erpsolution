<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JournalEntry extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'entry_reference',
        'entry_date',
        'description',
        'total_debit',
        'total_credit',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['entry_reference', 'entry_date', 'total_debit', 'total_credit', 'status'])
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
     * Get the journal entry items for this entry.
     */
    public function journalEntryItems(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class, 'journal_entry_id');
    }

    /**
     * Get the entry status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Check if entry is balanced.
     */
    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    /**
     * Check if entry is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if entry is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if entry is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Approve journal entry.
     */
    public function approve(User $user): void
    {
        if (!$this->isBalanced()) {
            throw new \Exception('Cannot approve unbalanced journal entry');
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        // Update account balances for all items
        foreach ($this->journalEntryItems as $item) {
            $item->account->updateBalance();
        }
    }

    /**
     * Reject journal entry.
     */
    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    /**
     * Cancel journal entry.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Get the balance difference.
     */
    public function getBalanceDifferenceAttribute(): float
    {
        return $this->total_debit - $this->total_credit;
    }

    /**
     * Get the formatted balance difference.
     */
    public function getFormattedBalanceDifferenceAttribute(): string
    {
        $difference = $this->balance_difference;
        $prefix = $difference > 0 ? '+' : '';
        return $prefix . '₦' . number_format($difference, 2);
    }

    /**
     * Scope for approved entries.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for pending entries.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
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

    /**
     * Scope for balanced entries.
     */
    public function scopeBalanced($query)
    {
        return $query->whereRaw('ABS(total_debit - total_credit) < 0.01');
    }

    /**
     * Scope for unbalanced entries.
     */
    public function scopeUnbalanced($query)
    {
        return $query->whereRaw('ABS(total_debit - total_credit) >= 0.01');
    }
}
