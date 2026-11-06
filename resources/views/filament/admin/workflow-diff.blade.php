@php
    /** @var array{base: string|null, changes: list<array{field: string, old: mixed, new: mixed}>} $diff */
    $format = function (mixed $value): string {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? __('app.yes') : __('app.no');
        }
        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => is_scalar($item) ? (string) $item : json_encode($item), $value));
        }

        return (string) $value;
    };
    $label = function (string $field): string {
        $parts = explode('.', $field);
        $last = end($parts);
        foreach (["cases.fields.{$last}", "hearings.fields.{$last}", "cases.parties.{$last}", "cases.stages.{$last}", "cases.sentences.{$last}", "cases.measures.{$last}"] as $key) {
            if (__($key) !== $key) {
                $parts[count($parts) - 1] = __($key);
                break;
            }
        }

        return implode(' › ', array_map(fn ($part) => is_numeric($part) ? '#'.$part : str_replace('_', ' ', (string) $part), $parts));
    };
@endphp
<div class="space-y-3" data-workflow-diff>
    <p class="text-sm text-gray-600 dark:text-gray-400">
        @if ($diff['base'] === null)
            {{ __('workflow.diff.first_submission') }}
        @else
            {{ __('workflow.diff.compared_with', ['status' => \App\Enums\WorkflowStatus::from($diff['base'])->getLabel()]) }}
        @endif
    </p>

    @if (count($diff['changes']) === 0)
        <p class="text-sm">{{ __('workflow.diff.no_changes') }}</p>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-3 py-2 font-medium">{{ __('workflow.diff.field') }}</th>
                        <th class="px-3 py-2 font-medium">{{ __('workflow.diff.before') }}</th>
                        <th class="px-3 py-2 font-medium">{{ __('workflow.diff.after') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach (array_slice($diff['changes'], 0, 200) as $change)
                        <tr>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">{{ $label($change['field']) }}</td>
                            <td class="px-3 py-2 align-top text-gray-500 line-through decoration-gray-400 dark:text-gray-400">{{ $format($change['old']) }}</td>
                            <td class="bg-gold-50 px-3 py-2 align-top font-semibold text-navy-900 dark:bg-white/5 dark:text-white">{{ $format($change['new']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
