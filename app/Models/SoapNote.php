<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoapNote extends Model
{
    protected $fillable = [
        'encounter_id',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'ai_summary',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }
}
