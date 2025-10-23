<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RevenueCollectionItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'revenue_collection_id',
        'item_name',
        'description',
        'quantity',
        'unit_price',
        'total_amount',
        'tax_amount',
        'net_amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['item_name', 'quantity', 'unit_price', 'total_amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the revenue collection for this item.
     */
    public function revenueCollection(): BelongsTo
    {
        return $this->belongsTo(RevenueCollection::class, 'revenue_collection_id');
    }

    /**
     * Get the formatted unit price for display.
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return '₦' . number_format($this->unit_price, 2);
    }

    /**
     * Get the formatted total amount for display.
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return '₦' . number_format($this->total_amount, 2);
    }

    /**
     * Get the formatted tax amount for display.
     */
    public function getFormattedTaxAmountAttribute(): string
    {
        return '₦' . number_format($this->tax_amount, 2);
    }

    /**
     * Get the formatted net amount for display.
     */
    public function getFormattedNetAmountAttribute(): string
    {
        return '₦' . number_format($this->net_amount, 2);
    }

    /**
     * Calculate total amount based on quantity and unit price.
     */
    public function calculateTotalAmount(): void
    {
        $this->total_amount = $this->quantity * $this->unit_price;
    }

    /**
     * Calculate tax amount (assuming 5% tax rate).
     */
    public function calculateTaxAmount(): void
    {
        $this->tax_amount = $this->total_amount * 0.05;
    }

    /**
     * Calculate net amount (total - tax).
     */
    public function calculateNetAmount(): void
    {
        $this->net_amount = $this->total_amount - $this->tax_amount;
    }

    /**
     * Calculate all amounts.
     */
    public function calculateAmounts(): void
    {
        $this->calculateTotalAmount();
        $this->calculateTaxAmount();
        $this->calculateNetAmount();
    }
}
