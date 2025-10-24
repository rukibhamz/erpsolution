<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MaintenanceLog extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'item_id',
        'maintenance_type',
        'description',
        'maintenance_date',
        'cost',
        'technician',
        'technician_contact',
        'next_maintenance_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'cost' => 'decimal:2',
        'next_maintenance_date' => 'date',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['maintenance_type', 'description', 'cost', 'maintenance_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the inventory item for this maintenance log.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    /**
     * Get the user who created this maintenance log.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the maintenance type badge color.
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->maintenance_type) {
            'preventive' => 'green',
            'corrective' => 'red',
            'predictive' => 'blue',
            'emergency' => 'orange',
            default => 'gray',
        };
    }

    /**
     * Check if maintenance is preventive.
     */
    public function isPreventive(): bool
    {
        return $this->maintenance_type === 'preventive';
    }

    /**
     * Check if maintenance is corrective.
     */
    public function isCorrective(): bool
    {
        return $this->maintenance_type === 'corrective';
    }

    /**
     * Check if maintenance is predictive.
     */
    public function isPredictive(): bool
    {
        return $this->maintenance_type === 'predictive';
    }

    /**
     * Check if maintenance is emergency.
     */
    public function isEmergency(): bool
    {
        return $this->getAttribute('maintenance_type') === 'emergency';
    }

    /**
     * Check if maintenance is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->getAttribute('next_maintenance_date') && $this->getAttribute('next_maintenance_date') < now();
    }

    /**
     * Check if maintenance is due soon.
     */
    public function isDueSoon(): bool
    {
        return $this->getAttribute('next_maintenance_date') && 
               $this->getAttribute('next_maintenance_date') <= now()->addDays(7) && 
               $this->getAttribute('next_maintenance_date') >= now();
    }

    /**
     * Get the formatted cost for display.
     */
    public function getFormattedCostAttribute(): string
    {
        return '₦' . number_format((float) $this->cost, 2);
    }

    /**
     * Get the days until next maintenance.
     */
    public function getDaysUntilNextMaintenanceAttribute(): int
    {
        if ($this->getAttribute('next_maintenance_date')) {
            return now()->diffInDays($this->getAttribute('next_maintenance_date'), false);
        }
        
        return 0;
    }

    /**
     * Scope for preventive maintenance.
     */
    public function scopePreventive($query)
    {
        return $query->where('maintenance_type', 'preventive');
    }

    /**
     * Scope for corrective maintenance.
     */
    public function scopeCorrective($query)
    {
        return $query->where('maintenance_type', 'corrective');
    }

    /**
     * Scope for predictive maintenance.
     */
    public function scopePredictive($query)
    {
        return $query->where('maintenance_type', 'predictive');
    }

    /**
     * Scope for emergency maintenance.
     */
    public function scopeEmergency($query)
    {
        return $query->where('maintenance_type', 'emergency');
    }

    /**
     * Scope for overdue maintenance.
     */
    public function scopeOverdue($query)
    {
        return $query->where('next_maintenance_date', '<', now());
    }

    /**
     * Scope for due soon maintenance.
     */
    public function scopeDueSoon($query)
    {
        return $query->whereBetween('next_maintenance_date', [now(), now()->addDays(7)]);
    }

    /**
     * Scope for maintenance by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('maintenance_date', [$startDate, $endDate]);
    }
}
