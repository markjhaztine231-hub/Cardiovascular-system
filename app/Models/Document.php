<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    public const REVIEW_THRESHOLD = 0.70;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'uploaded_by',
        'type',
        'original_filename',
        'image_path',
        'ocr_text',
        'ocr_raw',
        'ocr_confidence',
        'ocr_status',
        'ocr_error',
    ];

    protected $casts = [
        'ocr_raw' => 'array',
        'ocr_confidence' => 'float',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }

    /**
     * True while the text is not trustworthy enough to read without checking the
     * scan. PP-OCRv5 reports a real per-line score, so this is measured.
     */
    public function requiresReview(): bool
    {
        return $this->ocr_status !== 'completed'
            || $this->ocr_confidence === null
            || $this->ocr_confidence < self::REVIEW_THRESHOLD;
    }

    /**
     * Per-line recognition results, text first for stable ordering.
     */
    public function lines(): array
    {
        return (array) data_get($this->ocr_raw, 'lines', []);
    }

    public function engine(): ?string
    {
        return data_get($this->ocr_raw, 'engine');
    }

    /**
     * Ordered, typed page regions (heading/body/list/table/...) in reading order.
     */
    public function layout(): array
    {
        return (array) data_get($this->ocr_raw, 'layout', []);
    }

    /**
     * Non-text marks found on the page: seals, signatures, QR codes, tables,
     * checkboxes and figures, each with a bounding box.
     */
    public function visuals(): array
    {
        return (array) data_get($this->ocr_raw, 'visuals', []);
    }

    /**
     * Counts per visual kind, e.g. ['seal' => 1, 'table' => 2].
     */
    public function visualSummary(): array
    {
        $counts = [];

        foreach ($this->visuals() as $visual) {
            $kind = $visual['kind'] ?? null;

            if (is_string($kind) && $kind !== '') {
                $counts[$kind] = ($counts[$kind] ?? 0) + 1;
            }
        }

        ksort($counts);

        return $counts;
    }
}
