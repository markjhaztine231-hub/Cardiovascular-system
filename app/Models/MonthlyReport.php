<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyReport extends Model
{
    public const SCOPE_PROVIDER = 'provider';

    public const SCOPE_PRACTICE = 'practice';

    public const SCOPE_PROVIDER_LABEL = 'Personal Monthly Report';

    public const SCOPE_PRACTICE_LABEL = 'Practice-Wide Monthly Report';

    protected $fillable = [
        'provider_id',
        'scope',
        'month',
        'report_text',
        'stats',
    ];

    protected $casts = [
        'stats' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function scopeForProvider(Builder $query, int $providerId): Builder
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeForDoctor(Builder $query, int $userId): Builder
    {
        return $query
            ->where('scope', self::SCOPE_PROVIDER)
            ->whereHas('provider', fn ($q) => $q->where('user_id', $userId));
    }

    /**
     * Practice-wide reports carry no provider_id, so they are addressed by
     * scope alone. Scoping on `scope` here keeps forDoctor() from ever picking
     * one up via the provider relation.
     */
    public function scopePractice(Builder $query): Builder
    {
        return $query
            ->where('scope', self::SCOPE_PRACTICE)
            ->whereNull('provider_id');
    }

    public function scopeForMonth(Builder $query, string $month): Builder
    {
        return $query->where('month', $month);
    }

    public function isPracticeWide(): bool
    {
        return $this->scope === self::SCOPE_PRACTICE;
    }

    public function isPersonal(): bool
    {
        return $this->scope === self::SCOPE_PROVIDER;
    }

    /**
     * Human-readable title for a report scope. Used by the Inertia payload,
     * the PDF export, and the report history so the two report types are
     * never presented as the same document.
     */
    public static function labelForScope(?string $scope): string
    {
        return $scope === self::SCOPE_PRACTICE
            ? self::SCOPE_PRACTICE_LABEL
            : self::SCOPE_PROVIDER_LABEL;
    }
}
