<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalRequest extends Model
{
    protected $fillable = [
        'encounter_id',
        'patient_id',
        'provider_id',
        'type',
        'body',
        'priority',
        'status',
        'created_by',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'lab' => 'Laboratory',
            'imaging' => 'Imaging',
            'referral' => 'Referral',
            default => 'Other',
        };
    }
}
