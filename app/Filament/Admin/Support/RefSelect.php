<?php

namespace App\Filament\Admin\Support;

use App\Models\Reference\ReferenceModel;
use App\Models\User;
use App\Services\RecentValues;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;

/**
 * Searchable select for reference data that lists the user's recently used values first
 * (proposal B6.4). Inactive values stay selectable only if they are already the current value.
 */
final class RefSelect
{
    /**
     * @param  class-string<ReferenceModel>  $model
     * @param  (Closure(Builder<ReferenceModel>, Get): mixed)|null  $scope
     */
    public static function make(string $field, string $model, ?Closure $scope = null): Select
    {
        return Select::make($field)
            ->options(function (Get $get, Select $component) use ($field, $model, $scope): array {
                $current = $component->getState();
                $query = $model::query();

                if ($model::isTranslatable() || $model::labelColumn() === 'name') {
                    $query->orderBy($model::labelColumn());
                }

                $query->where(function (Builder $query) use ($current, $scope, $get): void {
                    $query->where(function (Builder $query) use ($scope, $get): void {
                        $query->where('is_active', true);

                        if ($scope !== null) {
                            $scope($query, $get);
                        }
                    });

                    // The current value stays selectable even if it is inactive or outside the scope.
                    if (filled($current) && ! is_array($current)) {
                        $query->orWhereKey($current);
                    }
                });

                $options = $query->get()
                    ->mapWithKeys(fn (ReferenceModel $record): array => [$record->getKey() => $record->label()])
                    ->all();

                $user = auth()->user();

                return RecentValues::group($user instanceof User ? $user : null, $field, $options);
            })
            ->searchable()
            ->optionsLimit(500);
    }
}
