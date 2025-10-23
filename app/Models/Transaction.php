<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Transaction extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'transaction_reference',
        'account_id',
        'transaction_type',
        'amount',
        'description',
        'transaction_date',
        'category',
        'subcategory',
        'payment_method',
        'reference_number',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['transaction_reference', 'amount', 'transaction_type', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the account for this transaction.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who created this transaction.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this transaction.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the journal entry items for this transaction.
     */
    public function journalEntryItems(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class);
    }

    /**
     * Get the transaction type badge color.
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->transaction_type) {
            'income' => 'green',
            'expense' => 'red',
            'transfer' => 'blue',
            'adjustment' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Get the transaction status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Check if transaction is income.
     */
    public function isIncome(): bool
    {
        return $this->transaction_type === 'income';
    }

    /**
     * Check if transaction is expense.
     */
    public function isExpense(): bool
    {
        return $this->transaction_type === 'expense';
    }

    /**
     * Check if transaction is transfer.
     */
    public function isTransfer(): bool
    {
        return $this->transaction_type === 'transfer';
    }

    /**
     * Check if transaction is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve transaction.
     */
    public function approve(User $user): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        // Update account balance
        $this->account->updateBalance();
    }

    /**
     * Reject transaction.
     */
    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    /**
     * Cancel transaction.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Get the formatted amount for display.
     */
    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->isIncome() ? '+' : '-';
        return $prefix . '₦' . number_format($this->amount, 2);
    }

    /**
     * Scope for approved transactions.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for transactions by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope for transactions by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope for income transactions.
     */
    public function scopeIncome($query)
    {
        return $query->where('transaction_type', 'income');
    }

    /**
     * Scope for expense transactions.
     */
    public function scopeExpense($query)
    {
        return $query->where('transaction_type', 'expense');
    }
}
