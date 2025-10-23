<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JournalEntryItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit_amount',
        'credit_amount',
        'description',
        'reference',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['account_id', 'debit_amount', 'credit_amount', 'description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the journal entry for this item.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Get the account for this item.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the item amount (debit or credit).
     */
    public function getAmountAttribute(): float
    {
        return $this->debit_amount > 0 ? $this->debit_amount : $this->credit_amount;
    }

    /**
     * Get the item type (debit or credit).
     */
    public function getTypeAttribute(): string
    {
        return $this->debit_amount > 0 ? 'debit' : 'credit';
    }

    /**
     * Get the formatted amount for display.
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₦' . number_format($this->amount, 2);
    }

    /**
     * Get the item type badge color.
     */
    public function getTypeColorAttribute(): string
    {
        return $this->type === 'debit' ? 'green' : 'red';
    }

    /**
     * Check if item is a debit.
     */
    public function isDebit(): bool
    {
        return $this->debit_amount > 0;
    }

    /**
     * Check if item is a credit.
     */
    public function isCredit(): bool
    {
        return $this->credit_amount > 0;
    }

    /**
     * Get the account balance impact.
     */
    public function getBalanceImpactAttribute(): float
    {
        if ($this->isDebit()) {
            return $this->debit_amount;
        } else {
            return -$this->credit_amount;
        }
    }

    /**
     * Scope for debit items.
     */
    public function scopeDebit($query)
    {
        return $query->where('debit_amount', '>', 0);
    }

    /**
     * Scope for credit items.
     */
    public function scopeCredit($query)
    {
        return $query->where('credit_amount', '>', 0);
    }

    /**
     * Scope for items by account.
     */
    public function scopeByAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }
}
