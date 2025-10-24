<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Repair extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'item_id',
        'repair_reference',
        'description',
        'repair_date',
        'cost',
        'repair_status',
        'technician',
        'technician_contact',
        'completion_date',
        'warranty_period',
        'warranty_expiry',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'repair_date' => 'date',
        'cost' => 'decimal:2',
        'completion_date' => 'date',
        'warranty_expiry' => 'date',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['repair_reference', 'description', 'cost', 'repair_status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the inventory item for this repair.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    /**
     * Get the user who created this repair.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the repair status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->repair_status) {
            'pending' => 'yellow',
            'in_progress' => 'blue',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    /**
     * Check if repair is pending.
     */
    public function isPending(): bool
    {
        return $this->repair_status === 'pending';
    }

    /**
     * Check if repair is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->repair_status === 'in_progress';
    }

    /**
     * Check if repair is completed.
     */
    public function isCompleted(): bool
    {
        return $this->repair_status === 'completed';
    }

    /**
     * Check if repair is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->repair_status === 'cancelled';
    }

    /**
     * Check if repair is under warranty.
     */
    public function isUnderWarranty(): bool
    {
        return $this->warranty_expiry && $this->warranty_expiry > now();
    }

    /**
     * Get the formatted cost for display.
     */
    public function getFormattedCostAttribute(): string
    {
        return '₦' . number_format((float) $this->cost, 2);
    }

    /**
     * Get the repair duration in days.
     */
    public function getDurationInDaysAttribute(): int
    {
        if ($this->completion_date) {
            return $this->repair_date->diffInDays($this->completion_date);
        }
        
        return $this->repair_date->diffInDays(now());
    }

    /**
     * Mark repair as in progress.
     */
    public function markInProgress(): void
    {
        $this->update(['repair_status' => 'in_progress']);
    }

    /**
     * Mark repair as completed.
     */
    public function markCompleted(): void
    {
        $this->update([
            'repair_status' => 'completed',
            'completion_date' => now(),
        ]);
    }

    /**
     * Mark repair as cancelled.
     */
    public function markCancelled(): void
    {
        $this->update(['repair_status' => 'cancelled']);
    }

    /**
     * Scope for pending repairs.
     */
    public function scopePending($query)
    {
        return $query->where('repair_status', 'pending');
    }

    /**
     * Scope for in progress repairs.
     */
    public function scopeInProgress($query)
    {
        return $query->where('repair_status', 'in_progress');
    }

    /**
     * Scope for completed repairs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('repair_status', 'completed');
    }

    /**
     * Scope for repairs by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('repair_date', [$startDate, $endDate]);
    }
}
