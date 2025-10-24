<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UtilityMeter extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'meter_number',
        'utility_type_id',
        'property_id',
        'location',
        'installation_date',
        'last_reading',
        'last_reading_date',
        'status',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'last_reading' => 'decimal:2',
        'last_reading_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['meter_number', 'utility_type_id', 'property_id', 'last_reading', 'status', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the utility type for this meter.
     */
    public function utilityType(): BelongsTo
    {
        return $this->belongsTo(UtilityType::class, 'utility_type_id');
    }

    /**
     * Get the property for this meter.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Get the utility readings for this meter.
     */
    public function utilityReadings(): HasMany
    {
        return $this->hasMany(UtilityReading::class, 'meter_id');
    }

    /**
     * Get the meter status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'green',
            'inactive' => 'gray',
            'maintenance' => 'yellow',
            'faulty' => 'red',
            default => 'gray',
        };
    }

    /**
     * Check if meter is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if meter is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Check if meter is under maintenance.
     */
    public function isUnderMaintenance(): bool
    {
        return $this->getAttribute('status') === 'maintenance';
    }

    /**
     * Check if meter is faulty.
     */
    public function isFaulty(): bool
    {
        return $this->getAttribute('status') === 'faulty';
    }

    /**
     * Get the total consumption for a date range.
     */
    public function getTotalConsumption($startDate, $endDate): float
    {
        $readings = $this->utilityReadings()
            ->whereBetween('reading_date', [$startDate, $endDate])
            ->orderBy('reading_date')
            ->get();

        if ($readings->count() < 2) {
            return 0;
        }

        $totalConsumption = 0;
        for ($i = 1; $i < $readings->count(); $i++) {
            $previousReading = $readings[$i - 1];
            $currentReading = $readings[$i];
            $consumption = $currentReading->reading - $previousReading->reading;
            $totalConsumption += $consumption;
        }

        return $totalConsumption;
    }

    /**
     * Get the average daily consumption for a date range.
     */
    public function getAverageDailyConsumption($startDate, $endDate): float
    {
        $totalConsumption = $this->getTotalConsumption($startDate, $endDate);
        $days = $startDate->diffInDays($endDate);
        
        return $days > 0 ? $totalConsumption / $days : 0;
    }

    /**
     * Get the last reading date.
     */
    public function getLastReadingDateAttribute(): ?string
    {
        $lastReading = $this->utilityReadings()
            ->orderBy('reading_date', 'desc')
            ->first();
            
        return $lastReading ? $lastReading->reading_date->format('Y-m-d') : null;
    }

    /**
     * Get the days since last reading.
     */
    public function getDaysSinceLastReadingAttribute(): int
    {
        if (!$this->getAttribute('last_reading_date')) {
            return 0;
        }
        
        return now()->diffInDays($this->getAttribute('last_reading_date'));
    }

    /**
     * Check if meter needs reading.
     */
    public function needsReading(): bool
    {
        return $this->getAttribute('days_since_last_reading') > 30; // More than 30 days since last reading
    }

    /**
     * Scope for active meters.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for meters by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for meters needing reading.
     */
    public function scopeNeedsReading($query)
    {
        return $query->where('last_reading_date', '<', now()->subDays(30))
                    ->orWhereNull('last_reading_date');
    }
}
