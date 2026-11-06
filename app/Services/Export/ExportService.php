<?php

namespace App\Services\Export;

use App\Enums\ExportFormat;
use App\Models\ExportLog;
use App\Models\User;
use BackedEnum;
use Closure;
use DateTimeInterface;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV / XLSX exports (proposal B6.6). Every export is logged in export_logs and in the
 * audit log before the file is streamed. Column definitions decide what leaves the system;
 * none of them include party names.
 */
class ExportService
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, Closure(TModel): mixed>  $columns  header => value getter
     * @param  array<string, mixed>  $filters  what the user filtered on (stored in the log)
     */
    public function stream(
        string $resource,
        Builder $query,
        array $columns,
        ExportFormat $format,
        string $context,
        ?User $user,
        array $filters = [],
        ?int $tokenId = null,
    ): StreamedResponse {
        $rowCount = (clone $query)->count();
        $this->log($resource, $format, $context, $user, $filters, $rowCount, $tokenId);

        $filename = $resource.'-'.now()->format('Ymd-His').'.'.$format->value;

        return response()->streamDownload(function () use ($query, $columns, $format): void {
            $writer = $format === ExportFormat::Xlsx ? new XlsxWriter : new CsvWriter;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(array_keys($columns)));

            foreach ($query->lazy(500) as $record) {
                $writer->addRow(Row::fromValues(array_map(fn (Closure $value) => self::cell($value($record)), array_values($columns))));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => $format === ExportFormat::Xlsx
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function log(string $resource, ExportFormat|string $format, string $context, ?User $user, array $filters, int $rowCount, ?int $tokenId = null): ExportLog
    {
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();
        $format = $format instanceof ExportFormat ? $format->value : $format;

        $log = ExportLog::query()->create([
            'user_id' => $user?->id,
            'context' => $context,
            'resource' => $resource,
            'format' => $format,
            'filters' => $filters === [] ? null : $filters,
            'row_count' => $rowCount,
            'token_id' => $tokenId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
        ]);

        activity('exports')
            ->causedBy($user)
            ->performedOn($log)
            ->event('exported')
            ->withProperties(['resource' => $resource, 'format' => $format, 'context' => $context, 'rows' => $rowCount])
            ->log("Export of {$resource} ({$rowCount} rows)");

        return $log;
    }

    public static function cell(mixed $value): string|int|float|bool|null
    {
        return match (true) {
            $value === null => null,
            $value instanceof HasLabel => (string) $value->getLabel(),
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            is_array($value) => implode(', ', array_map(fn ($item) => (string) (is_scalar($item) ? $item : json_encode($item)), $value)),
            is_bool($value), is_int($value), is_float($value) => $value,
            default => (string) $value,
        };
    }
}
