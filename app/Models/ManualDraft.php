<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualDraft extends Model
{
    protected $fillable = [
        'encounter_id',
        'requesting_physician',
        'title',
        'draft',
        'conclusion',
        'attachments',
        'performed_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'performed_at' => 'datetime',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }
}
