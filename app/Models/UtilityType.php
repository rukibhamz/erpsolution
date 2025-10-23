<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UtilityType extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'unit_of_measure',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'unit_of_measure', 'color', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the utility meters for this type.
     */
    public function utilityMeters(): HasMany
    {
        return $this->hasMany(UtilityMeter::class, 'utility_type_id');
    }

    /**
     * Get the utility readings for this type.
     */
    public function utilityReadings(): HasMany
    {
        return $this->hasMany(UtilityReading::class, 'utility_type_id');
    }

    /**
     * Scope for active utility types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
