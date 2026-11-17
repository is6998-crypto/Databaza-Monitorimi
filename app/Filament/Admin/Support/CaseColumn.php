<?php

namespace App\Filament\Admin\Support;

use App\Models\CourtCase;
use Closure;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

/**
 * The case a row belongs to, in two short lines: the court's case number, and the court under it in grey.
 * One long "number · court" label used to wrap over three or four lines and made every row tall.
 */
final class CaseColumn
{
    /**
     * @param  Closure(Model): (CourtCase|null)  $case  how to reach the case from the table's record
     */
    public static function make(string $name, Closure $case): TextColumn
    {
        return TextColumn::make($name)
            ->label(__('hearings.fields.case'))
            ->state(fn (Model $record): string => ($found = $case($record)) !== null ? ($found->court_case_number ?: '#'.$found->id) : '—')
            ->description(fn (Model $record): ?string => $case($record)?->court?->label())
            ->weight('medium')
            ->extraCellAttributes(['class' => 'cw-case-cell']);
    }
}
