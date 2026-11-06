<?php

namespace App\Filament\Research\Pages;

use App\Enums\ExportFormat;
use App\Models\ExportLog;
use App\Models\User;
use App\Services\Export\ExportService;
use App\Services\Export\ResearchExportColumns;
use App\Services\Research\ResearchQueries;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Anonymized record-level downloads for approved researchers. Every download is logged.
 */
class Datasets extends Page
{
    protected string $view = 'filament.research.datasets';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'datasets';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isApprovedResearcher();
    }

    public function getTitle(): string
    {
        return __('research.datasets.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('research.datasets.title');
    }

    /**
     * @return list<string>
     */
    public static function datasetKeys(): array
    {
        return ['cases', 'parties', 'hearings'];
    }

    /**
     * Streams one anonymized dataset and logs the export (also used by the privacy test).
     */
    public static function streamDataset(string $dataset, ExportFormat $format, User $user): StreamedResponse
    {
        $export = app(ExportService::class);
        $resource = 'research:'.$dataset;

        return match ($dataset) {
            'cases' => $export->stream($resource, ResearchQueries::cases(), ResearchExportColumns::cases(), $format, ExportLog::CONTEXT_RESEARCHER, $user),
            'parties' => $export->stream($resource, ResearchQueries::parties(), ResearchExportColumns::parties(), $format, ExportLog::CONTEXT_RESEARCHER, $user),
            'hearings' => $export->stream($resource, ResearchQueries::hearings(), ResearchExportColumns::hearings(), $format, ExportLog::CONTEXT_RESEARCHER, $user),
            default => throw new InvalidArgumentException("Unknown dataset [{$dataset}]."),
        };
    }

    public function downloadAction(): Action
    {
        return Action::make('download')
            ->label(__('research.datasets.download'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->schema([
                Select::make('format')->label(__('exports.fields.format'))->options(ExportFormat::options())->default(ExportFormat::Csv->value)->required(),
            ])
            ->action(function (array $data, array $arguments): StreamedResponse {
                $user = auth()->user();
                abort_unless($user instanceof User && static::canAccess(), 403);

                $dataset = (string) ($arguments['dataset'] ?? '');
                abort_unless(in_array($dataset, self::datasetKeys(), true), 404);

                return self::streamDataset($dataset, ExportFormat::from($data['format']), $user);
            });
    }
}
