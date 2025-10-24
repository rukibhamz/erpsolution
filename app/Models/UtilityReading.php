<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UtilityReading extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'meter_id',
        'utility_type_id',
        'reading',
        'reading_date',
        'previous_reading',
        'consumption',
        'rate_per_unit',
        'total_amount',
        'notes',
        'read_by',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'reading' => 'decimal:2',
        'previous_reading' => 'decimal:2',
        'consumption' => 'decimal:2',
        'rate_per_unit' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'reading_date' => 'date',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['reading', 'reading_date', 'consumption', 'total_amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the meter for this reading.
     */
    public function meter(): BelongsTo
    {
        return $this->belongsTo(UtilityMeter::class, 'meter_id');
    }

    /**
     * Get the utility type for this reading.
     */
    public function utilityType(): BelongsTo
    {
        return $this->belongsTo(UtilityType::class, 'utility_type_id');
    }

    /**
     * Get the user who read this meter.
     */
    public function readBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'read_by');
    }

    /**
     * Get the user who verified this reading.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Check if reading is verified.
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Get the formatted consumption for display.
     */
    public function getFormattedConsumptionAttribute(): string
    {
        return number_format($this->consumption, 2) . ' ' . $this->utilityType->unit_of_measure;
    }

    /**
     * Get the formatted total amount for display.
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return '₦' . number_format((float) $this->total_amount, 2);
    }

    /**
     * Get the consumption trend (increase/decrease from previous reading).
     */
    public function getConsumptionTrendAttribute(): string
    {
        if ($this->previous_reading === 0) {
            return 'new';
        }
        
        $percentageChange = (($this->consumption - $this->previous_reading) / $this->previous_reading) * 100;
        
        if ($percentageChange > 10) {
            return 'high';
        } elseif ($percentageChange < -10) {
            return 'low';
        } else {
            return 'normal';
        }
    }

    /**
     * Get the consumption trend color.
     */
    public function getConsumptionTrendColorAttribute(): string
    {
        return match ($this->consumption_trend) {
            'high' => 'red',
            'low' => 'green',
            'normal' => 'blue',
            'new' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Calculate consumption from previous reading.
     */
    public function calculateConsumption(): void
    {
        $this->consumption = $this->reading - $this->previous_reading;
        $this->total_amount = $this->consumption * $this->rate_per_unit;
    }

    /**
     * Verify the reading.
     */
    public function verify(User $user): void
    {
        $this->update([
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);
    }

    /**
     * Scope for verified readings.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Scope for unverified readings.
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('verified_at');
    }

    /**
     * Scope for readings by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('reading_date', [$startDate, $endDate]);
    }

    /**
     * Scope for readings by utility type.
     */
    public function scopeByUtilityType($query, $utilityTypeId)
    {
        return $query->where('utility_type_id', $utilityTypeId);
    }

    /**
     * Scope for high consumption readings.
     */
    public function scopeHighConsumption($query)
    {
        return $query->whereRaw('consumption > (SELECT AVG(consumption) * 1.5 FROM utility_readings WHERE utility_type_id = utility_readings.utility_type_id)');
    }
}
