<?php

namespace App\Services\Research;

use App\Enums\CaseType;
use App\Models\CaseParty;
use App\Models\CourtCase;
use App\Models\Hearing;
use Illuminate\Database\Eloquent\Builder;

/**
 * Queries behind the researcher area and API: verified data only, with optional filters
 * (case type, court, year / date range). Used together with the anonymized resources.
 */
final class ResearchQueries
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<CourtCase>
     */
    public static function cases(array $filters = []): Builder
    {
        return CourtCase::query()
            ->verified()
            ->with(['court', 'department', 'stages', 'parties', 'charges.offence.criminalCode', 'charges.offence.category', 'hearings' => fn ($query) => $query->verified()])
            ->when(CaseType::tryFrom((string) ($filters['type'] ?? '')), fn (Builder $query, CaseType $type) => $query->where('type', $type->value))
            ->when(is_numeric($filters['court_id'] ?? null), fn (Builder $query) => $query->where('court_id', (int) $filters['court_id']))
            ->when(is_numeric($filters['year'] ?? null), fn (Builder $query) => $query->whereHas('stages', fn (Builder $stages) => $stages->whereYear('date', (int) $filters['year'])))
            ->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Hearing>
     */
    public static function hearings(array $filters = []): Builder
    {
        return Hearing::query()
            ->verified()
            ->whereHas('case', fn (Builder $case) => $case->verified())
            ->with(['case', 'court'])
            ->when(CaseType::tryFrom((string) ($filters['type'] ?? '')), fn (Builder $query, CaseType $type) => $query->whereHas('case', fn (Builder $case) => $case->where('type', $type->value)))
            ->when(is_numeric($filters['court_id'] ?? null), fn (Builder $query) => $query->where('court_id', (int) $filters['court_id']))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('hearing_date', '>=', $from))
            ->when($filters['until'] ?? null, fn (Builder $query, string $until) => $query->whereDate('hearing_date', '<=', $until))
            ->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<CaseParty>
     */
    public static function parties(array $filters = []): Builder
    {
        return CaseParty::query()
            ->whereHas('case', fn (Builder $case) => $case->verified()
                ->when(CaseType::tryFrom((string) ($filters['type'] ?? '')), fn (Builder $query, CaseType $type) => $query->where('type', $type->value))
                ->when(is_numeric($filters['court_id'] ?? null), fn (Builder $query) => $query->where('court_id', (int) $filters['court_id'])))
            ->with(['case', 'nationality', 'economicStatus', 'defendantPosition', 'charges.offence.criminalCode', 'charges.offence.category', 'sentences'])
            ->orderBy('id');
    }
}
