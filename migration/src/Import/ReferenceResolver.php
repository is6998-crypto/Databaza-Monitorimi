<?php

namespace Migration\Import;

use App\Enums\CourtLevel;
use App\Models\Reference\Court;
use App\Models\Reference\CourtBranch;
use App\Models\Reference\CriminalCode;
use App\Models\Reference\CriminalOffence;
use App\Models\Reference\Department;
use App\Models\Reference\Judge;
use App\Models\Reference\Monitor;
use App\Models\Reference\ProsecutionOffice;
use App\Models\Reference\Prosecutor;
use App\Models\Reference\ReferenceModel;
use Illuminate\Support\Str;
use Migration\Support\Text;

/**
 * Matches legacy reference values (free text, often with duplicates and spelling
 * variants) to the new reference tables.
 *
 * - Courts, branches, departments and prosecution offices are a fixed public structure:
 *   they are matched (names, codes, city names with Albanian endings, aliases), never created.
 * - Judges and prosecutors are matched by name regardless of case, spacing, titles and
 *   word order, and created (inactive) when missing.
 * - Simple lists (nationality, position, …) and unknown articles are created inactive and
 *   reported, so an administrator can merge them with "merge duplicates".
 * - Monitors are matched to existing monitor accounts (e-mail, name or code), never created.
 */
final class ReferenceResolver
{
    private const INSTITUTION_STOPWORDS = [
        'gjykata', 'gjykates', 'gjykate', 'themelore', 'themelor', 'basic', 'court', 'of', 'the', 'e', 'i', 'te', 'ne', 'per',
        'gjth', 'gj', 'th', 'dega', 'dege', 'branch', 'prokuroria', 'prokurorise', 'prosecution', 'office', 'in', 'pth',
    ];

    /** @var array<string, int|array{0: int, 1: int|null}|null> */
    private array $cache = [];

    /** @var list<string> cache keys created since the last checkpoint */
    private array $pending = [];

    /** @var array<string, int> type => created this run */
    private array $created = [];

    /** @var array<string, int> type => created since the last checkpoint */
    private array $pendingCreated = [];

    /** @var array<string, true> types whose table is already in the cache */
    private array $loaded = [];

    /** @var array<string, int>|null */
    private ?array $monitorIndex = null;

    /** @var array<string, array<string, string>>|null */
    private ?array $aliases = null;

    /** @var list<array{id: int, code: string, level: string, keys: list<string>, stem: string}>|null */
    private ?array $courts = null;

    /** @var list<array{id: int, court_id: int, stem: string, key: string}>|null */
    private ?array $branches = null;

    // ---------------------------------------------------------------- courts and institutions

    /**
     * @return array{0: int, 1: int|null}|null [court id, branch id]
     */
    public function court(?string $raw): ?array
    {
        if ($raw === null) {
            return null;
        }

        $key = Text::key($raw);
        $cacheKey = 'court|'.$key;

        if (! array_key_exists($cacheKey, $this->cache)) {
            $this->cache[$cacheKey] = $this->matchCourt($key);
        }

        $match = $this->cache[$cacheKey];

        return is_array($match) ? $match : null;
    }

    public function branch(int $courtId, ?string $raw): ?int
    {
        if ($raw === null) {
            return null;
        }

        $tokens = $this->stems(Text::key($raw));

        foreach ($this->branches() as $branch) {
            if ($branch['court_id'] === $courtId && (in_array($branch['stem'], $tokens, true) || $branch['key'] === Text::key($raw))) {
                return $branch['id'];
            }
        }

        return null;
    }

    public function department(?string $raw): ?int
    {
        if ($raw === null) {
            return null;
        }

        return $this->remember('department|'.Text::key($raw), function () use ($raw): ?int {
            $key = Text::key($raw);
            $code = $this->alias('department', $key) ?? Str::upper($raw);
            $byCode = Department::query()->where('code', $code)->value('id');

            if ($byCode !== null) {
                return (int) $byCode;
            }

            foreach (Department::query()->get() as $department) {
                if (in_array($key, [Text::key($department->name_sq), Text::key($department->name_en)], true)) {
                    return $department->id;
                }
            }

            return null;
        });
    }

    public function departmentByCode(?string $code): ?int
    {
        if ($code === null || $code === '') {
            return null;
        }

        return $this->remember('department-code|'.$code, fn (): ?int => ($id = Department::query()->where('code', $code)->value('id')) === null ? null : (int) $id);
    }

    public function prosecutionOffice(?string $raw): ?int
    {
        if ($raw === null) {
            return null;
        }

        return $this->remember('prosecution-office|'.Text::key($raw), function () use ($raw): ?int {
            $key = Text::key($raw);
            $offices = ProsecutionOffice::query()->get();

            foreach ($offices as $office) {
                if (in_array($key, [Text::key($office->name_sq), Text::key($office->name_en)], true)) {
                    return $office->id;
                }
            }

            $tokens = explode(' ', $key);

            if (array_intersect($tokens, ['speciale', 'special', 'sprk']) !== []) {
                return $offices->first(fn (ProsecutionOffice $office): bool => $office->level->value === 'special')?->id;
            }

            if (array_filter($tokens, fn (string $token): bool => str_starts_with($token, 'apel') || str_starts_with($token, 'appel')) !== []) {
                return $offices->first(fn (ProsecutionOffice $office): bool => $office->level->value === 'appellate')?->id;
            }

            $stems = $this->stems($key);
            $matches = $offices->filter(fn (ProsecutionOffice $office): bool => $office->level->value === 'basic' && in_array(Text::stem(Text::key($office->city)), $stems, true));

            return $matches->count() === 1 ? $matches->first()?->id : null;
        });
    }

    // ---------------------------------------------------------------- people

    public function judge(?string $raw, ?int $courtId = null): ?int
    {
        return $this->person(Judge::class, 'judge', $raw, ['court_id' => $courtId]);
    }

    public function prosecutor(?string $raw, ?int $officeId = null): ?int
    {
        return $this->person(Prosecutor::class, 'prosecutor', $raw, ['prosecution_office_id' => $officeId]);
    }

    /**
     * An existing monitor (by e-mail, name or code). Monitors are user accounts, so they are never created here.
     */
    public function monitor(?string $raw): ?int
    {
        if ($raw === null) {
            return null;
        }

        $index = $this->monitorIndex();
        $email = $this->alias('monitor', Text::key($raw)) ?? (str_contains($raw, '@') ? $raw : null);

        if ($email !== null && isset($index['email|'.Str::lower(trim($email))])) {
            return $index['email|'.Str::lower(trim($email))];
        }

        return $index['name|'.Text::personKey($raw)] ?? $index['code|'.Text::key($raw)] ?? null;
    }

    // ---------------------------------------------------------------- lists and offences

    /**
     * A simple reference list value (nationality, position, …), created inactive when missing.
     *
     * @param  class-string<ReferenceModel>  $model
     * @return array{0: int, 1: bool}|null [id, created]
     */
    public function lookup(string $model, ?string $raw): ?array
    {
        if ($raw === null) {
            return null;
        }

        $type = class_basename($model);
        $cacheKey = $type.'|'.$this->lookupKey($raw);

        if (! isset($this->loaded[$type])) {
            foreach ($model::query()->get() as $record) {
                foreach (['name_sq', 'name_en', 'name_sr'] as $column) {
                    $value = $record->getAttribute($column);

                    if (is_string($value) && $value !== '') {
                        $this->cache[$type.'|'.$this->lookupKey($value)] ??= (int) $record->getKey();
                    }
                }
            }

            $this->loaded[$type] = true;
        }

        if (isset($this->cache[$cacheKey]) && is_int($this->cache[$cacheKey])) {
            return [$this->cache[$cacheKey], false];
        }

        $record = new $model;
        $record->forceFill(['name_sq' => Str::limit($raw, 250, ''), 'is_active' => false])->save();

        $this->created($cacheKey, $type, (int) $record->getKey());

        return [(int) $record->getKey(), true];
    }

    /**
     * @return array{0: int|null, 1: bool, 2: string} [offence id, created, description of the article]
     */
    public function offence(?string $codeRaw, ?string $articleRaw, ?string $titleRaw, ?string $date): array
    {
        $codeId = $this->criminalCode($codeRaw, $date);
        $code = $codeId === null ? '?' : (string) CriminalCode::query()->whereKey($codeId)->value('code');
        $article = $articleRaw !== null && preg_match('/(\d{1,4})/', $articleRaw, $match) ? ltrim($match[1], '0') : null;
        $description = trim($code.' art. '.($article ?? '?').($titleRaw !== null ? ' ('.$titleRaw.')' : ''));

        if ($codeId === null || ($article === null && $titleRaw === null)) {
            return [null, false, $description];
        }

        if ($article !== null) {
            $cacheKey = 'offence|'.$codeId.'|'.$article;

            if (! array_key_exists($cacheKey, $this->cache)) {
                $id = CriminalOffence::query()->where('criminal_code_id', $codeId)->where('article_number', $article)->value('id');
                $this->cache[$cacheKey] = $id === null ? null : (int) $id;
            }

            if (is_int($this->cache[$cacheKey])) {
                return [$this->cache[$cacheKey], false, $description];
            }

            $offence = new CriminalOffence;
            $offence->forceFill([
                'criminal_code_id' => $codeId,
                'article_number' => $article,
                'title_sq' => Str::limit($titleRaw ?? 'Neni '.$article, 250, ''),
                'is_active' => false,
            ])->save();

            $this->created($cacheKey, 'CriminalOffence', $offence->id);

            return [$offence->id, true, $description];
        }

        $key = Text::key($titleRaw);
        $id = $this->remember('offence-title|'.$codeId.'|'.$key, function () use ($codeId, $key): ?int {
            foreach (CriminalOffence::query()->where('criminal_code_id', $codeId)->get() as $offence) {
                if (in_array($key, [Text::key($offence->title_sq), Text::key($offence->title_en)], true)) {
                    return $offence->id;
                }
            }

            return null;
        });

        return [$id, false, $description];
    }

    public function criminalCode(?string $raw, ?string $date): ?int
    {
        $code = null;

        if ($raw !== null) {
            $key = Text::key($raw);
            $code = $this->alias('criminal_code', $key) ?? (CriminalCode::query()->where('code', Str::upper($raw))->exists() ? Str::upper($raw) : null);

            if ($code === null) {
                foreach ([['2019', 'KPRK-2019'], ['2012', 'KPK-2012'], ['2003', 'UNMIK-2003/25']] as [$year, $candidate]) {
                    if (str_contains($key, $year)) {
                        $code = $candidate;
                    }
                }
            }
        }

        if ($code === null) {
            foreach ((array) config('legacy.criminal_codes_by_date', []) as $rule) {
                $from = $rule['from'] ?? null;

                if ($from === null || ($date !== null && $date >= $from)) {
                    $code = (string) $rule['code'];

                    break;
                }
            }
        }

        if ($code === null) {
            return null;
        }

        return $this->remember('criminal-code|'.$code, fn (): ?int => ($id = CriminalCode::query()->where('code', $code)->value('id')) === null ? null : (int) $id);
    }

    // ---------------------------------------------------------------- bookkeeping

    /**
     * @return array<string, int> type => records created this run
     */
    public function createdCounts(): array
    {
        return $this->created;
    }

    public function checkpoint(): void
    {
        $this->pending = [];
        $this->pendingCreated = [];
    }

    /** The unit of work was rolled back: forget the records it created. */
    public function rollback(): void
    {
        foreach ($this->pending as $cacheKey) {
            unset($this->cache[$cacheKey]);
        }

        foreach ($this->pendingCreated as $type => $count) {
            $this->created[$type] = max(0, ($this->created[$type] ?? 0) - $count);
        }

        $this->checkpoint();
    }

    // ---------------------------------------------------------------- internals

    /**
     * @param  class-string<Judge|Prosecutor>  $model
     * @param  array<string, int|null>  $attributes
     */
    private function person(string $model, string $type, ?string $raw, array $attributes): ?int
    {
        $key = Text::personKey($raw);

        if ($raw === null || $key === '') {
            return null;
        }

        $cacheKey = $type.'|'.$key;

        if (! isset($this->loaded[$type])) {
            foreach ($model::query()->get(['id', 'name']) as $person) {
                $this->cache[$type.'|'.Text::personKey($person->name)] ??= $person->id;
            }

            $this->loaded[$type] = true;
        }

        if (isset($this->cache[$cacheKey]) && is_int($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $person = new $model;
        $person->forceFill(['name' => Str::limit(Str::title(Str::lower($raw)), 250, ''), 'is_active' => false, ...array_filter($attributes)])->save();

        $this->created($cacheKey, class_basename($model), $person->id);

        return $person->id;
    }

    /**
     * @return array{0: int, 1: int|null}|null
     */
    private function matchCourt(string $key): ?array
    {
        if ($key === '') {
            return null;
        }

        $courts = $this->courts();
        $alias = $this->alias('court', $key);

        foreach ($courts as $court) {
            if (($alias !== null && $court['code'] === $alias) || in_array($key, $court['keys'], true)) {
                return [$court['id'], null];
            }
        }

        $tokens = explode(' ', $key);
        $level = match (true) {
            array_filter($tokens, fn (string $token): bool => str_starts_with($token, 'apel') || str_starts_with($token, 'appeal')) !== [] => CourtLevel::Appeal->value,
            array_filter($tokens, fn (string $token): bool => str_starts_with($token, 'suprem')) !== [] => CourtLevel::Supreme->value,
            default => null,
        };

        if ($level !== null) {
            $matches = array_values(array_filter($courts, fn (array $court): bool => $court['level'] === $level));

            return count($matches) === 1 ? [$matches[0]['id'], null] : null;
        }

        $stems = $this->stems($key);
        $branches = array_values(array_filter($this->branches(), fn (array $branch): bool => in_array($branch['stem'], $stems, true)));

        if (count($branches) === 1) {
            return [$branches[0]['court_id'], $branches[0]['id']];
        }

        $matches = array_values(array_filter($courts, fn (array $court): bool => $court['level'] === CourtLevel::Basic->value && in_array($court['stem'], $stems, true)));

        return count($matches) === 1 ? [$matches[0]['id'], null] : null;
    }

    /**
     * @return list<array{id: int, code: string, level: string, keys: list<string>, stem: string}>
     */
    private function courts(): array
    {
        return $this->courts ??= Court::query()->get()->map(fn (Court $court): array => [
            'id' => $court->id,
            'code' => (string) $court->code,
            'level' => $court->level->value,
            'keys' => array_values(array_filter([Text::key($court->name_sq), Text::key($court->name_en), Text::key($court->code), Text::key($court->name_sr)])),
            'stem' => Text::stem(Text::key($court->city)),
        ])->values()->all();
    }

    /**
     * @return list<array{id: int, court_id: int, stem: string, key: string}>
     */
    private function branches(): array
    {
        return $this->branches ??= CourtBranch::query()->get()->map(fn (CourtBranch $branch): array => [
            'id' => $branch->id,
            'court_id' => $branch->court_id,
            'stem' => Text::stem(Text::key($branch->city)),
            'key' => Text::key($branch->name_sq),
        ])->values()->all();
    }

    /**
     * @return list<string>
     */
    private function stems(string $key): array
    {
        $tokens = array_diff(explode(' ', $key), self::INSTITUTION_STOPWORDS, ['']);

        return array_values(array_map(fn (string $token): string => Text::stem($token), $tokens));
    }

    private function lookupKey(string $value): string
    {
        return implode(' ', array_map(fn (string $token): string => Text::stem($token), array_filter(explode(' ', Text::key($value)))));
    }

    /**
     * @return array<string, int>
     */
    private function monitorIndex(): array
    {
        if ($this->monitorIndex !== null) {
            return $this->monitorIndex;
        }

        $index = [];

        foreach (Monitor::query()->with('user')->get() as $monitor) {
            if ($monitor->user !== null) {
                $index['email|'.Str::lower($monitor->user->email)] = $monitor->id;
                $index['name|'.Text::personKey($monitor->user->name)] ??= $monitor->id;
            }

            if (filled($monitor->code)) {
                $index['code|'.Text::key($monitor->code)] = $monitor->id;
            }
        }

        return $this->monitorIndex = $index;
    }

    private function alias(string $type, string $key): ?string
    {
        if ($this->aliases === null) {
            $this->aliases = [];

            foreach ((array) config('legacy.aliases', []) as $aliasType => $map) {
                foreach ((array) $map as $from => $to) {
                    $this->aliases[(string) $aliasType][Text::key((string) $from)] = (string) $to;
                }
            }
        }

        return $this->aliases[$type][$key] ?? null;
    }

    /**
     * @param  callable(): ?int  $resolve
     */
    private function remember(string $cacheKey, callable $resolve): ?int
    {
        if (! array_key_exists($cacheKey, $this->cache)) {
            $this->cache[$cacheKey] = $resolve();
        }

        $value = $this->cache[$cacheKey];

        return is_int($value) ? $value : null;
    }

    private function created(string $cacheKey, string $type, int $id): void
    {
        $this->cache[$cacheKey] = $id;
        $this->pending[] = $cacheKey;
        $this->created[$type] = ($this->created[$type] ?? 0) + 1;
        $this->pendingCreated[$type] = ($this->pendingCreated[$type] ?? 0) + 1;
    }
}
