<?php

namespace App\Livewire\Portal;

use App\Enums\CaseType;
use App\Enums\StageOutcome;
use App\Enums\StageType;
use App\Livewire\Portal\Concerns\KeepsPortalLocale;
use App\Models\CourtCase;
use App\Models\Reference\Category;
use App\Models\Reference\Court;
use App\Services\Portal\PublicCasePresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Anonymized public case search: verified cases only, searchable by offence, court, type,
 * year and outcome — never by name or case number.
 */
class CaseSearch extends Component
{
    use KeepsPortalLocale;
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public ?string $type = null;

    #[Url]
    public ?string $court = null;

    #[Url]
    public ?string $year = null;

    #[Url]
    public ?string $category = null;

    #[Url]
    public ?string $outcome = null;

    public function updated(string $property): void
    {
        if ($property !== 'paginators') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['q', 'type', 'court', 'year', 'category', 'outcome']);
        $this->resetPage();
    }

    public function render(): View
    {
        $term = trim(mb_substr($this->q, 0, 100));

        $query = CourtCase::query()
            ->verified()
            ->with(PublicCasePresenter::relations())
            ->when(CaseType::tryFrom((string) $this->type), fn (Builder $query, CaseType $type) => $query->where('type', $type->value))
            ->when(is_numeric($this->court), fn (Builder $query) => $query->where('court_id', (int) $this->court))
            ->when(is_numeric($this->year), fn (Builder $query) => $query->whereHas('stages', fn (Builder $stages) => $stages->whereYear('date', (int) $this->year)))
            ->when(is_numeric($this->category), fn (Builder $query) => $query->whereHas('charges.offence', fn (Builder $offence) => $offence->where('category_id', (int) $this->category)))
            ->when(StageOutcome::tryFrom((string) $this->outcome), fn (Builder $query, StageOutcome $outcome) => $query->whereHas('stages', fn (Builder $stages) => $stages->where('stage', StageType::JudgmentAnnounced->value)->where('outcome', $outcome->value)))
            ->when($term !== '', fn (Builder $query) => $query->whereHas('charges.offence', fn (Builder $offence) => $offence
                ->where('title_sq', 'ilike', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%')
                ->orWhere('title_en', 'ilike', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%')
                ->orWhere('article_number', $term)))
            ->latest('updated_at');

        $cases = $query->paginate(12);

        return view('livewire.portal.case-search', [
            'cases' => $cases,
            'items' => $cases->getCollection()->map(fn (CourtCase $case): array => PublicCasePresenter::summary($case)),
            'courts' => Court::selectOptions(),
            'types' => CaseType::options(),
            'categories' => Category::selectOptions(),
            'outcomes' => StageType::optionsForOutcome(StageType::JudgmentAnnounced),
            'years' => range((int) now()->format('Y'), 2010),
        ]);
    }
}
