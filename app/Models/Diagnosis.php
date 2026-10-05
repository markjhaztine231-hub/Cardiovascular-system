<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Diagnosis extends Model
{
    protected $fillable = [
        'encounter_id',
        'icd_code',
        'description',
        'is_primary',
        'diagnosed_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'diagnosed_at' => 'datetime',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }
}
