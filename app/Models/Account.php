<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Account extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'account_code',
        'account_name',
        'description',
        'account_type',
        'account_category',
        'opening_balance',
        'current_balance',
        'is_active',
        'is_system_account',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'is_system_account' => 'boolean',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['account_code', 'account_name', 'account_type', 'current_balance', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the journal entry items for this account.
     */
    public function journalEntryItems(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class);
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
            'revenue' => 'yellow',
            'expense' => 'orange',
            default => 'gray',
        };
    }

    /**
     * Get the account category badge color.
     */
    public function getCategoryColorAttribute(): string
    {
        return match ($this->account_category) {
            'current_asset' => 'green',
            'fixed_asset' => 'emerald',
            'current_liability' => 'red',
            'long_term_liability' => 'rose',
            'equity' => 'blue',
            'revenue' => 'yellow',
            'operating_expense' => 'orange',
            'non_operating_expense' => 'amber',
            default => 'gray',
        };
    }

    /**
     * Check if account is an asset.
     */
    public function isAsset(): bool
    {
        return $this->account_type === 'asset';
    }

    /**
     * Check if account is a liability.
     */
    public function isLiability(): bool
    {
        return $this->account_type === 'liability';
    }

    /**
     * Check if account is equity.
     */
    public function isEquity(): bool
    {
        return $this->account_type === 'equity';
    }

    /**
     * Check if account is revenue.
     */
    public function isRevenue(): bool
    {
        return $this->account_type === 'revenue';
    }

    /**
     * Check if account is expense.
     */
    public function isExpense(): bool
    {
        return $this->account_type === 'expense';
    }

    /**
     * Update account balance.
     */
    public function updateBalance(): void
    {
        $debitTotal = $this->journalEntryItems()->sum('debit_amount');
        $creditTotal = $this->journalEntryItems()->sum('credit_amount');
        
        $this->update([
            'current_balance' => $this->opening_balance + $debitTotal - $creditTotal
        ]);
    }

    /**
     * Get account balance for display.
     */
    public function getBalanceForDisplayAttribute(): string
    {
        $balance = $this->current_balance;
        
        // For assets and expenses, positive balance is normal
        // For liabilities, equity, and revenue, negative balance is normal
        if (in_array($this->account_type, ['liability', 'equity', 'revenue'])) {
            $balance = -$balance;
        }
        
        return '₦' . number_format($balance, 2);
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

    /**
     * Scope for accounts by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('account_category', $category);
    }

    /**
     * Scope for non-system accounts.
     */
    public function scopeUserAccounts($query)
    {
        return $query->where('is_system_account', false);
    }
}
