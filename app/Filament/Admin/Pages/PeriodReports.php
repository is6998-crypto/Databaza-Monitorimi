<?php

namespace App\Filament\Admin\Pages;

use App\Enums\CaseType;
use App\Enums\NavigationGroup;
use App\Models\ExportLog;
use App\Models\Reference\Court;
use App\Models\User;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use App\Services\Documents\DocumentRenderer;
use App\Services\Export\ExportService;
use App\Services\Reports\PeriodReport;
use App\Support\Permissions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * "Period reports": a monitoring report for a quarter, half year, year or chosen dates, as Word or PDF, for
 * funders and partners. Approved data only; small numbers hidden by default. Every download is logged.
 *
 * @property-read Schema $form
 */
class PeriodReports extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Analytics;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'period-reports';

    protected string $view = 'filament.admin.pages.period-reports';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->checkPermissionTo(Permissions::VIEW_ANALYTICS) && $user->checkPermissionTo(Permissions::EXPORT_DATA);
    }

    public static function getNavigationLabel(): string
    {
        return __('period_reports.navigation');
    }

    public function getTitle(): string
    {
        return __('period_reports.title');
    }

    public function getSubheading(): ?string
    {
        return __('period_reports.subheading');
    }

    public function mount(): void
    {
        // The quarter that ended last.
        $previous = Carbon::now((string) config('courtwatch.display_timezone'))->subQuarter();

        $this->form->fill([
            'period' => 'quarter',
            'year' => $previous->year,
            'quarter' => $previous->quarter,
            'half' => $previous->month <= 6 ? 1 : 2,
            'format' => 'docx',
            'suppress' => true,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $years = range((int) now()->year, (int) now()->year - 8);

        return $schema
            ->statePath('data')
            ->components([
                ToggleButtons::make('period')
                    ->label(__('period_reports.form.period'))
                    ->options((array) __('period_reports.form.periods'))
                    ->inline()
                    ->grouped()
                    ->live()
                    ->required(),
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    Select::make('year')
                        ->label(__('period_reports.form.year'))
                        ->options(collect($years)->mapWithKeys(fn (int $year): array => [$year => (string) $year])->all())
                        ->live()
                        ->required(fn (Get $get): bool => $get('period') !== 'custom')
                        ->visible(fn (Get $get): bool => $get('period') !== 'custom'),
                    Select::make('quarter')
                        ->label(__('period_reports.form.quarter'))
                        ->options([1 => 'T1', 2 => 'T2', 3 => 'T3', 4 => 'T4'])
                        ->live()
                        ->required(fn (Get $get): bool => $get('period') === 'quarter')
                        ->visible(fn (Get $get): bool => $get('period') === 'quarter'),
                    Select::make('half')
                        ->label(__('period_reports.form.half'))
                        ->options([1 => '1', 2 => '2'])
                        ->live()
                        ->required(fn (Get $get): bool => $get('period') === 'half')
                        ->visible(fn (Get $get): bool => $get('period') === 'half'),
                    DatePicker::make('from')
                        ->label(__('period_reports.form.from'))
                        ->live()
                        ->required(fn (Get $get): bool => $get('period') === 'custom')
                        ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                    DatePicker::make('until')
                        ->label(__('period_reports.form.until'))
                        ->live()
                        ->afterOrEqual('from')
                        ->required(fn (Get $get): bool => $get('period') === 'custom')
                        ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                ]),
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    Select::make('court_id')
                        ->label(__('period_reports.form.court'))
                        ->options(fn (): array => Court::selectOptions())
                        ->placeholder(__('period_reports.form.all_courts'))
                        ->searchable()
                        ->live(),
                    Select::make('case_type')
                        ->label(__('period_reports.form.case_type'))
                        ->options(CaseType::options())
                        ->placeholder(__('period_reports.form.all_types'))
                        ->live(),
                ]),
                ToggleButtons::make('format')
                    ->label(__('documents.format'))
                    ->options((array) __('documents.formats'))
                    ->inline()
                    ->grouped()
                    ->required(),
                Toggle::make('suppress')
                    ->label(__('period_reports.form.suppress'))
                    ->helperText(__('period_reports.form.suppress_help')),
            ]);
    }

    /**
     * The date range and filters the form describes.
     *
     * @param  array<string, mixed>  $data
     */
    public static function filters(array $data): AnalyticsFilters
    {
        $year = (int) ($data['year'] ?? now()->year);

        [$from, $until] = match ($data['period'] ?? 'quarter') {
            'year' => [Carbon::create($year, 1, 1), Carbon::create($year, 12, 31)],
            'half' => (int) ($data['half'] ?? 1) === 1
                ? [Carbon::create($year, 1, 1), Carbon::create($year, 6, 30)]
                : [Carbon::create($year, 7, 1), Carbon::create($year, 12, 31)],
            'custom' => [
                filled($data['from'] ?? null) ? Carbon::parse((string) $data['from']) : null,
                filled($data['until'] ?? null) ? Carbon::parse((string) $data['until']) : null,
            ],
            default => [
                Carbon::create($year, (max(1, min(4, (int) ($data['quarter'] ?? 1))) - 1) * 3 + 1, 1),
                Carbon::create($year, (max(1, min(4, (int) ($data['quarter'] ?? 1))) - 1) * 3 + 1, 1)?->endOfQuarter(),
            ],
        };

        return new AnalyticsFilters(
            from: $from?->startOfDay(),
            until: $until?->startOfDay(),
            courtId: filled($data['court_id'] ?? null) ? (int) $data['court_id'] : null,
            caseType: filled($data['case_type'] ?? null) ? CaseType::tryFrom((string) $data['case_type']) : null,
        );
    }

    public function download(PeriodReport $reports, DocumentRenderer $renderer, ExportService $exports): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();
        $filters = static::filters($data);
        $format = ($data['format'] ?? 'docx') === 'pdf' ? 'pdf' : 'docx';
        $suppress = (bool) ($data['suppress'] ?? true);

        $document = $reports->make($filters, $suppress);
        $binary = $format === 'pdf' ? $renderer->pdf($document) : $renderer->docx($document);

        $user = auth()->user();
        $exports->log('period_report', $format, ExportLog::CONTEXT_ADMIN, $user instanceof User ? $user : null, [...$filters->toArray(), 'suppress' => $suppress], 1);

        $name = Str::slug(__('period_reports.document.title', ['period' => PeriodReport::periodLabel($filters)])).'.'.$format;

        return response()->streamDownload(fn () => print ($binary), $name, [
            'Content-Type' => $format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $filters = static::filters($this->data ?? []);
        $analytics = AnalyticsService::for($filters);
        $ready = $filters->from !== null && $filters->until !== null;

        return [
            'period' => PeriodReport::periodLabel($filters),
            'ready' => $ready,
            'cases' => $ready ? (int) $analytics->monitoredCases()->count() : null,
            'hearings' => $ready ? (int) $analytics->hearings()->count() : null,
        ];
    }
}
