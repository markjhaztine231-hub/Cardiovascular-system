<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmbulatoryBloodPressureMonitoring extends Model
{
    protected $fillable = [
        'encounter_id',
        'payload',
        'performed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'performed_at' => 'datetime',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }
}