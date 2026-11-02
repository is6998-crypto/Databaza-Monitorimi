<?php

namespace Database\Seeders;

use App\Enums\CourtLevel;
use App\Models\Reference\Court;
use App\Models\Reference\Judge;
use App\Models\Reference\ProsecutionOffice;
use App\Models\Reference\Prosecutor;
use Database\Faker\KosovoProvider;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;

/**
 * FAKE judges and prosecutors for development (random name combinations, not real people).
 * Also adds two deliberate duplicates so the "merge duplicates" action can be tried.
 */
class DemoReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $faker = FakerFactory::create();
        $faker->seed(1912);
        $kosovo = new KosovoProvider($faker);

        if (Judge::query()->exists()) {
            return;
        }

        foreach (Court::query()->get() as $court) {
            $count = match ($court->level) {
                CourtLevel::Basic => 6,
                CourtLevel::Appeal => 4,
                CourtLevel::Supreme => 3,
            };

            foreach (range(1, $count) as $i) {
                Judge::query()->create(['name' => $kosovo->kosovoName(), 'court_id' => $court->id, 'is_active' => true]);
            }
        }

        foreach (ProsecutionOffice::query()->get() as $office) {
            foreach (range(1, 4) as $i) {
                Prosecutor::query()->create(['name' => $kosovo->kosovoName(), 'prosecution_office_id' => $office->id, 'is_active' => true]);
            }
        }

        // Duplicates as they appear in the legacy lists (different spelling / casing).
        $judge = Judge::query()->orderBy('id')->firstOrFail();
        Judge::query()->create(['name' => mb_strtoupper($judge->name), 'court_id' => $judge->court_id, 'is_active' => true]);

        $prosecutor = Prosecutor::query()->orderBy('id')->firstOrFail();
        Prosecutor::query()->create(['name' => $prosecutor->name.' ', 'prosecution_office_id' => $prosecutor->prosecution_office_id, 'is_active' => true]);
    }
}
