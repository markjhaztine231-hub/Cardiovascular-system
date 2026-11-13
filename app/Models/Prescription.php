<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    protected $fillable = [
        'encounter_id',
        'patient_id',
        'provider_id',
        'medication_name',
        'dosage',
        'frequency',
        'route',
        'duration_days',
        'start_date',
        'end_date',
        'instructions',
        'status',
        'is_freeform',
        'free_text',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_freeform' => 'boolean',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function medicationLogs(): HasMany
    {
        return $this->hasMany(MedicationLog::class);
    }

    public function getDosageLabelAttribute(): string
    {
        $parts = array_filter([
            $this->dosage,
            $this->frequency,
            $this->route,
        ]);

        return implode(' — ', $parts) ?: 'PRN';
    }
}
