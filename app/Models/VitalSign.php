<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VitalSign extends Model
{
    protected $fillable = [
        'encounter_id',
        'temperature_celsius',
        'heart_rate_bpm',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'respiratory_rate',
        'oxygen_saturation_percent',
        'weight_kg',
        'height_cm',
        'bmi',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }
}
