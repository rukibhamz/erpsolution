<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockMovement extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'item_id',
        'movement_type',
        'quantity',
        'reason',
        'reference',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['movement_type', 'quantity', 'reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the inventory item for this movement.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    /**
     * Get the user who created this movement.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the movement type badge color.
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->movement_type) {
            'in' => 'green',
            'out' => 'red',
            'adjustment' => 'blue',
            'transfer' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Check if movement is stock in.
     */
    public function isStockIn(): bool
    {
        return $this->movement_type === 'in';
    }

    /**
     * Check if movement is stock out.
     */
    public function isStockOut(): bool
    {
        return $this->movement_type === 'out';
    }

    /**
     * Check if movement is adjustment.
     */
    public function isAdjustment(): bool
    {
        return $this->movement_type === 'adjustment';
    }

    /**
     * Check if movement is transfer.
     */
    public function isTransfer(): bool
    {
        return $this->movement_type === 'transfer';
    }

    /**
     * Get the formatted quantity for display.
     */
    public function getFormattedQuantityAttribute(): string
    {
        $prefix = $this->isStockIn() ? '+' : '-';
        return $prefix . $this->quantity;
    }

    /**
     * Scope for stock in movements.
     */
    public function scopeStockIn($query)
    {
        return $query->where('movement_type', 'in');
    }

    /**
     * Scope for stock out movements.
     */
    public function scopeStockOut($query)
    {
        return $query->where('movement_type', 'out');
    }

    /**
     * Scope for movements by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
