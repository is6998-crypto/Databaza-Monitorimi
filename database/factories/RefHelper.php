<?php

namespace Database\Factories;

use App\Enums\CourtLevel;
use App\Enums\ProsecutionLevel;
use App\Models\Reference\Court;
use App\Models\Reference\Judge;
use App\Models\Reference\ProsecutionOffice;
use App\Models\Reference\Prosecutor;
use App\Models\Reference\ReferenceModel;

/**
 * Picks an existing reference row (or creates a minimal one) so factories work both
 * against a seeded database and in isolated tests.
 */
final class RefHelper
{
    /**
     * @template T of ReferenceModel
     *
     * @param  class-string<T>  $class
     * @param  array<string, mixed>  $attributes
     * @return T
     */
    public static function pick(string $class, array $attributes): ReferenceModel
    {
        /** @var T|null $existing */
        $existing = $class::query()->where('is_active', true)->inRandomOrder()->first();

        return $existing ?? $class::query()->create($attributes);
    }

    public static function court(): Court
    {
        return self::pick(Court::class, ['name_sq' => 'Gjykata Themelore Test', 'name_en' => 'Basic Court Test', 'level' => CourtLevel::Basic, 'code' => 'GJTH-T']);
    }

    public static function judge(?Court $court = null): Judge
    {
        $court ??= self::court();

        /** @var Judge|null $judge */
        $judge = Judge::query()->where('court_id', $court->id)->inRandomOrder()->first();

        return $judge ?? Judge::query()->create(['name' => fake()->firstName().' '.fake()->lastName(), 'court_id' => $court->id]);
    }

    public static function prosecutor(): Prosecutor
    {
        /** @var Prosecutor|null $prosecutor */
        $prosecutor = Prosecutor::query()->inRandomOrder()->first();

        if ($prosecutor !== null) {
            return $prosecutor;
        }

        $office = self::pick(ProsecutionOffice::class, ['name_sq' => 'Prokuroria Themelore Test', 'name_en' => 'Basic Prosecution Office Test', 'level' => ProsecutionLevel::Basic]);

        return Prosecutor::query()->create(['name' => fake()->firstName().' '.fake()->lastName(), 'prosecution_office_id' => $office->id]);
    }
}
