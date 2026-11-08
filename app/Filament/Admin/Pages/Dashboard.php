<?php

namespace App\Filament\Admin\Pages;

use App\Enums\CaseType;
use App\Filament\Admin\Widgets\Analytics;
use App\Filament\Admin\Widgets\MyWorkWidget;
use App\Filament\Admin\Widgets\WelcomeWidget;
use App\Models\Reference\Court;
use App\Support\Permissions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Unexposed;

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

    /**
     * The welcome band greets the user by name, so the "Dashboard" heading stays for screen readers only
     * (theme.css, .cw-dashboard).
     *
     * @return array<string>
     */
    #[Unexposed]
    public function getPageClasses(): array
    {
        return [...parent::getPageClasses(), 'cw-dashboard'];
    }

    public function filtersForm(Schema $schema): Schema
    {
        if (! (auth()->user()?->can(Permissions::VIEW_ANALYTICS) ?? false)) {
            return $schema->components([]);
        }

        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(['default' => 2, 'xl' => 4])
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

    /**
     * The welcome band sits above the filters and the analytics.
     *
     * @return list<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [WelcomeWidget::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    /**
     * @return list<class-string>
     */
    public function getWidgets(): array
    {
        return [
            MyWorkWidget::class,
            Analytics\AnalyticsOverview::class,
            Analytics\HearingsOverTimeChart::class,
            Analytics\CasesByCategoryChart::class,
            Analytics\PostponementByChart::class,
            Analytics\PostponementReasonsChart::class,
            Analytics\PunctualityChart::class,
            Analytics\PublicClosedChart::class,
            Analytics\ComplianceChart::class,
            Analytics\IndictmentOutcomesChart::class,
            Analytics\StageDurationsChart::class,
            Analytics\VerdictsChart::class,
            Analytics\SentenceDistributionChart::class,
            Analytics\AppealOutcomesChart::class,
            Analytics\SeizureConfiscationChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 2];
    }

    public function persistsFiltersInSession(): bool
    {
        return true;
    }
}
