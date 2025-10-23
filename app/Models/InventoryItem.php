<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'item_code',
        'name',
        'description',
        'category_id',
        'unit_of_measure',
        'purchase_price',
        'selling_price',
        'current_stock',
        'minimum_stock',
        'maximum_stock',
        'reorder_point',
        'supplier',
        'location',
        'status',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'current_stock' => 'integer',
        'minimum_stock' => 'integer',
        'maximum_stock' => 'integer',
        'reorder_point' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['item_code', 'name', 'current_stock', 'status', 'is_active'])
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
     * Get the stock movements for this item.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'item_id');
    }

    /**
     * Get the repairs for this item.
     */
    public function repairs(): HasMany
    {
        return $this->hasMany(Repair::class, 'item_id');
    }

    /**
     * Get the maintenance logs for this item.
     */
    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class, 'item_id');
    }

    /**
     * Get the item status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'available' => 'green',
            'low_stock' => 'yellow',
            'out_of_stock' => 'red',
            'discontinued' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Check if item is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Check if item is low stock.
     */
    public function isLowStock(): bool
    {
        return $this->status === 'low_stock';
    }

    /**
     * Check if item is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->status === 'out_of_stock';
    }

    /**
     * Check if item is discontinued.
     */
    public function isDiscontinued(): bool
    {
        return $this->status === 'discontinued';
    }

    /**
     * Check if item needs reordering.
     */
    public function needsReordering(): bool
    {
        return $this->current_stock <= $this->reorder_point;
    }

    /**
     * Get the total value of current stock.
     */
    public function getStockValueAttribute(): float
    {
        return $this->current_stock * $this->purchase_price;
    }

    /**
     * Get the formatted stock value.
     */
    public function getFormattedStockValueAttribute(): string
    {
        return '₦' . number_format($this->stock_value, 2);
    }

    /**
     * Update item status based on stock level.
     */
    public function updateStatus(): void
    {
        if ($this->current_stock <= 0) {
            $this->update(['status' => 'out_of_stock']);
        } elseif ($this->current_stock <= $this->minimum_stock) {
            $this->update(['status' => 'low_stock']);
        } else {
            $this->update(['status' => 'available']);
        }
    }

    /**
     * Add stock to the item.
     */
    public function addStock(int $quantity, string $reason = 'Stock added'): void
    {
        $this->current_stock += $quantity;
        $this->save();
        
        // Create stock movement record
        StockMovement::create([
            'item_id' => $this->id,
            'movement_type' => 'in',
            'quantity' => $quantity,
            'reason' => $reason,
            'created_by' => auth()->id(),
        ]);
        
        $this->updateStatus();
    }

    /**
     * Remove stock from the item.
     */
    public function removeStock(int $quantity, string $reason = 'Stock removed'): void
    {
        if ($this->current_stock < $quantity) {
            throw new \Exception('Insufficient stock');
        }
        
        $this->current_stock -= $quantity;
        $this->save();
        
        // Create stock movement record
        StockMovement::create([
            'item_id' => $this->id,
            'movement_type' => 'out',
            'quantity' => $quantity,
            'reason' => $reason,
            'created_by' => auth()->id(),
        ]);
        
        $this->updateStatus();
    }

    /**
     * Scope for active items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for items by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for low stock items.
     */
    public function scopeLowStock($query)
    {
        return $query->where('current_stock', '<=', DB::raw('minimum_stock'));
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
        return $query->where('current_stock', '<=', DB::raw('reorder_point'));
    }
}
