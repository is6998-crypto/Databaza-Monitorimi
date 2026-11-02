<?php

namespace App\Filament\Admin\Pages;

use App\Enums\CaseType;
use App\Models\Reference\Court;
use App\Support\Permissions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Staff dashboard. Analytics widgets (proposal B6.6) share the filters below:
 * date range, court and case type. Filters live in the URL so views can be shared.
 */
class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function getTitle(): string
    {
        return __('dashboard.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.title');
    }

    public function filtersForm(Schema $schema): Schema
    {
        if (! (auth()->user()?->can(Permissions::VIEW_ANALYTICS) ?? false)) {
            return $schema->components([]);
        }

        return $schema->components([
            Section::make()
                ->columns(['default' => 1, 'sm' => 2, 'xl' => 4])
                ->schema([
                    DatePicker::make('from')->label(__('dashboard.filters.from')),
                    DatePicker::make('until')->label(__('dashboard.filters.until')),
                    Select::make('court_id')
                        ->label(__('dashboard.filters.court'))
                        ->options(fn (): array => Court::selectOptions())
                        ->searchable(),
                    Select::make('case_type')
                        ->label(__('dashboard.filters.case_type'))
                        ->options(CaseType::options()),
                ]),
        ]);
    }

    public function persistsFiltersInSession(): bool
    {
        return true;
    }
}
