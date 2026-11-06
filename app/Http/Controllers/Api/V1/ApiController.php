<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CaseType;
use App\Http\Controllers\Controller;
use App\Models\ExportLog;
use App\Models\User;
use App\Services\Export\ExportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

abstract class ApiController extends Controller
{
    /**
     * Validates the shared query parameters and returns the filters that were given.
     *
     * @param  list<string>  $allowed
     * @return array<string, mixed>
     */
    protected function filters(Request $request, array $allowed): array
    {
        $rules = [
            'type' => ['nullable', 'string', Rule::enum(CaseType::class)],
            'case_type' => ['nullable', 'string', Rule::enum(CaseType::class)],
            'court_id' => ['nullable', 'integer', 'min:1'],
            'year' => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('courtwatch.api.max_page_size', 100)],
            'page' => ['nullable', 'integer', 'min:1'],
            'lang' => ['nullable', 'string', Rule::in((array) config('courtwatch.supported_locales'))],
        ];

        $validated = $request->validate(array_intersect_key($rules, array_flip([...$allowed, 'per_page', 'page', 'lang'])));

        return array_filter(
            array_intersect_key($validated, array_flip($allowed)),
            fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }

    protected function perPage(Request $request): int
    {
        return max(1, min((int) $request->integer('per_page', 25), (int) config('courtwatch.api.max_page_size', 100)));
    }

    /**
     * Every API response with record-level data counts as an export and is logged.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function logExport(Request $request, string $resource, int $rows, array $filters = []): void
    {
        $user = $request->user();
        $token = $user instanceof User ? $user->currentAccessToken() : null;

        app(ExportService::class)->log(
            'api:'.$resource,
            'json',
            ExportLog::CONTEXT_API,
            $user instanceof User ? $user : null,
            $filters,
            $rows,
            $token instanceof PersonalAccessToken ? (int) $token->getKey() : null,
        );
    }
}
