<?php

namespace App\Services\Search;

use App\Support\BlindIndex;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Global case search (proposal B6.6): case / indictment / police number, judge, offence
 * (substring + pg_trgm fuzzy matching) and party name (exact tokens, through the encrypted
 * names' blind index).
 *
 * Case numbers share most of their trigrams ("PKR.nr."), so fuzzy matching on numbers only
 * accepts near-identical strings (typos); substring matching covers partial numbers.
 */
class CaseSearch
{
    public const NUMBER_SIMILARITY = 0.65;

    public const NAME_SIMILARITY = 0.45;

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query  a query on the cases table
     * @return Builder<TModel>
     */
    public static function apply(Builder $query, string $search): Builder
    {
        $term = trim($search);

        if ($term === '') {
            return $query;
        }

        $like = '%'.self::escapeLike($term).'%';
        $tokens = BlindIndex::queryTokenHashes($term);
        $fuzzy = DB::getDriverName() === 'pgsql' && mb_strlen($term) >= 3;

        return $query->where(function (Builder $query) use ($like, $term, $tokens, $fuzzy): void {
            foreach (['court_case_number', 'indictment_number', 'police_case_number'] as $column) {
                $query->orWhere("cases.{$column}", 'ilike', $like);

                if ($fuzzy) {
                    $query->orWhereRaw("similarity(cases.{$column}, ?) > ?", [$term, self::NUMBER_SIMILARITY]);
                }
            }

            $query->orWhereHas('judge', function (Builder $judge) use ($like, $term, $fuzzy): void {
                $judge->where('name', 'ilike', $like);

                if ($fuzzy) {
                    $judge->orWhereRaw('similarity(judges.name, ?) > ?', [$term, self::NAME_SIMILARITY]);
                }
            });

            $query->orWhereHas('charges.offence', function (Builder $offence) use ($like, $term): void {
                $offence->where('title_sq', 'ilike', $like)
                    ->orWhere('title_en', 'ilike', $like)
                    ->orWhere('article_number', $term);
            });

            if ($tokens !== []) {
                // Every word of the query must match a word of the same party's name.
                $query->orWhereHas('parties', function (Builder $party) use ($tokens): void {
                    foreach ($tokens as $token) {
                        $party->where('name_tokens', 'like', '%'.$token.'%');
                    }
                });
            }
        });
    }

    /**
     * Orders by the best trigram similarity of the case numbers (PostgreSQL only).
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function rank(Builder $query, string $search): Builder
    {
        if (DB::getDriverName() !== 'pgsql' || trim($search) === '') {
            return $query;
        }

        return $query->orderByRaw(
            'greatest(similarity(coalesce(cases.court_case_number, \'\'), ?), similarity(coalesce(cases.indictment_number, \'\'), ?)) desc',
            [trim($search), trim($search)],
        );
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
