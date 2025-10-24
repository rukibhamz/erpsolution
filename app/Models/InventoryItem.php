<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'item_code',
        'item_name',
        'description',
        'category_id',
        'unit_price',
        'current_stock',
        'initial_stock',
        'reorder_level',
        'supplier',
        'supplier_contact',
        'location',
        'status',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['item_name', 'current_stock', 'status', 'unit_price'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the category for this item.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    /**
     * Get the item status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'green',
            'inactive' => 'gray',
            'discontinued' => 'red',
            default => 'gray',
        };
    }

    /**
     * Check if item is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if item is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }

    /**
     * Check if item needs reorder.
     */
    public function needsReorder(): bool
    {
        return $this->current_stock <= $this->reorder_level && $this->current_stock > 0;
    }

    /**
     * Check if item is low stock.
     */
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }

    /**
     * Get formatted unit price.
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return '₦' . number_format($this->unit_price, 2);
    }

    /**
     * Get total value.
     */
    public function getTotalValueAttribute(): float
    {
        return $this->current_stock * $this->unit_price;
    }

    /**
     * Get formatted total value.
     */
    public function getFormattedTotalValueAttribute(): string
    {
        return '₦' . number_format($this->total_value, 2);
    }

    /**
     * Scope for active items.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for low stock items.
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('current_stock <= reorder_level');
    }

    /**
     * Scope for out of stock items.
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('current_stock', 0);
    }

    /**
     * Scope for items needing reorder.
     */
    public function scopeNeedsReorder($query)
    {
        return $query->whereRaw('current_stock <= reorder_level AND current_stock > 0');
    }
}