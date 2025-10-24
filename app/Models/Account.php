<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Account extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'parent_account_id',
        'description',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['account_name', 'account_type', 'balance', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the transactions for this account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the journal entry items for this account.
     */
    public function journalEntryItems(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class);
    }

    /**
     * Get the parent account.
     */
    public function parentAccount()
    {
        return $this->belongsTo(Account::class, 'parent_account_id');
    }

    /**
     * Get the child accounts.
     */
    public function childAccounts(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_account_id');
    }

    /**
     * Get the account type badge color.
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->account_type) {
            'asset' => 'green',
            'liability' => 'red',
            'equity' => 'blue',
            'income' => 'yellow',
            'expense' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Check if account is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Update account balance based on transactions.
     */
    public function updateBalance(): void
    {
        $balance = $this->transactions()
            ->approved()
            ->sum(\DB::raw('CASE 
                WHEN transaction_type = "income" THEN amount 
                WHEN transaction_type = "expense" THEN -amount 
                WHEN transaction_type = "transfer" THEN 
                    CASE WHEN account_id = ' . $this->id . ' THEN amount ELSE -amount END
                ELSE 0 
            END'));

        $this->update(['balance' => $balance]);
    }

    /**
     * Get formatted balance.
     */
    public function getFormattedBalanceAttribute(): string
    {
        return '₦' . number_format($this->balance, 2);
    }

    /**
     * Scope for active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for accounts by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('account_type', $type);
    }
}