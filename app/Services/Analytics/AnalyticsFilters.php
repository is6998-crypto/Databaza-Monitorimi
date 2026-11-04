<?php

namespace App\Services\Analytics;

use App\Enums\CaseType;
use Illuminate\Support\Carbon;

/**
 * Filters shared by the staff dashboard and the public portal: date range, court, case type.
 */
final class AnalyticsFilters
{
    public function __construct(
        public readonly ?Carbon $from = null,
        public readonly ?Carbon $until = null,
        public readonly ?int $courtId = null,
        public readonly ?CaseType $caseType = null,
    ) {}

    /**
     * @param  array<string, mixed>|null  $input
     */
    public static function fromArray(?array $input): self
    {
        $input ??= [];

        $date = function (mixed $value): ?Carbon {
            if (blank($value)) {
                return null;
            }

            try {
                return Carbon::parse((string) $value)->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        };

        $type = $input['case_type'] ?? null;

        return new self(
            from: $date($input['from'] ?? null),
            until: $date($input['until'] ?? null),
            courtId: filled($input['court_id'] ?? null) ? (int) $input['court_id'] : null,
            caseType: $type instanceof CaseType ? $type : (is_string($type) ? CaseType::tryFrom($type) : null),
        );
    }

    /**
     * @return array<string, string|int|null>
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from?->toDateString(),
            'until' => $this->until?->toDateString(),
            'court_id' => $this->courtId,
            'case_type' => $this->caseType?->value,
        ];
    }

    public function cacheKey(): string
    {
        return md5((string) json_encode($this->toArray()));
    }
}
