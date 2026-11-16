<?php

namespace App\Services\Reference;

use App\Enums\CourtLevel;
use App\Models\Reference\Court;
use App\Models\Reference\CourtBranch;
use App\Models\Reference\Department;
use App\Models\Reference\Division;
use App\Models\Reference\ReferenceModel;

/**
 * The public structure of the courts of Kosovo: every court with its branches or chambers, the departments
 * and divisions, and which departments exist in which court and branch.
 *
 * Used by the ReferenceDataSeeder (new databases) and by a migration (existing databases). Idempotent: rows are
 * matched by code or Albanian name, nothing is deleted, and a court or branch whose departments were already
 * set in the staff panel keeps them.
 */
class CourtStructure
{
    /**
     * Basic courts: code => [genitive name (sq), name (en), city, [branch (sq) => branch (en)]].
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: array<string, string>}>
     */
    private const BASIC_COURTS = [
        'GJTH-PR' => ['Prishtinës', 'Prishtina', 'Prishtina', ['Podujevë' => 'Podujevo', 'Graçanicë' => 'Gračanica', 'Lipjan' => 'Lipljan', 'Gllogoc' => 'Gllogoc', 'Fushë Kosovë' => 'Fushë Kosovë']],
        'GJTH-MI' => ['Mitrovicës', 'Mitrovica', 'Mitrovica', ['Leposaviq' => 'Leposavić', 'Vushtrri' => 'Vushtrri', 'Zubin Potok' => 'Zubin Potok', 'Skenderaj' => 'Skenderaj']],
        'GJTH-PE' => ['Pejës', 'Peja', 'Peja', ['Istog' => 'Istog', 'Klinë' => 'Klina', 'Deçan' => 'Deçan']],
        'GJTH-PZ' => ['Prizrenit', 'Prizren', 'Prizren', ['Dragash' => 'Dragash', 'Suharekë' => 'Suhareka']],
        'GJTH-FE' => ['Ferizajt', 'Ferizaj', 'Ferizaj', ['Kaçanik' => 'Kaçanik', 'Shtërpcë' => 'Štrpce']],
        'GJTH-GJ' => ['Gjilanit', 'Gjilan', 'Gjilan', ['Kamenicë' => 'Kamenica', 'Viti' => 'Vitia', 'Novobërdë' => 'Novo Brdo']],
        'GJTH-GJK' => ['Gjakovës', 'Gjakova', 'Gjakova', ['Malishevë' => 'Malisheva', 'Rahovec' => 'Rahovec']],
    ];

    /**
     * Branches that were seeded under another name of the same town: old Albanian name => new Albanian name.
     *
     * @var array<string, string>
     */
    private const RENAMED_BRANCHES = [
        'Dega në Drenas' => 'Dega në Gllogoc',
    ];

    /**
     * The other courts: code => [name (sq), name (en), level, [chamber or division (sq) => (en)]].
     *
     * @var array<string, array{0: string, 1: string, 2: CourtLevel, 3: array<string, string>}>
     */
    private const OTHER_COURTS = [
        'GJS' => ['Gjykata Supreme', 'Supreme Court', CourtLevel::Supreme, ['Dhoma e Posaçme' => 'Special Chamber']],
        'GJA' => ['Gjykata e Apelit', 'Court of Appeals', CourtLevel::Appeal, ['Divizioni në Mitrovicë' => 'Division in Mitrovica']],
        'GJK' => ['Gjykata Komerciale', 'Commercial Court', CourtLevel::Commercial, ['Dhoma e Shkallës së Parë' => 'First Instance Chamber', 'Dhoma e Shkallës së Dytë' => 'Second Instance Chamber']],
        'GJAD' => ['Gjykata Administrative', 'Administrative Court', CourtLevel::Administrative, []],
    ];

    /**
     * Departments: code => [name (sq), name (en), [division (sq), division (en)]…].
     *
     * @var array<string, array{0: string, 1: string, 2: list<array{0: string, 1: string}>}>
     */
    private const DEPARTMENTS = [
        'GEN' => ['Departamenti i Përgjithshëm', 'General Department', [
            ['Divizioni Civil', 'Civil Division'],
            ['Divizioni Penal', 'Criminal Division'],
            ['Divizioni për Kundërvajtje', 'Minor Offences Division'],
        ]],
        'JUV' => ['Departamenti për të Mitur', 'Juvenile Department', []],
        'SER' => ['Departamenti për Krime të Rënda', 'Serious Crimes Department', []],
        'SPE' => ['Departamenti Special', 'Special Department', []],
        // Kept for older cases: these matters are now heard by the Commercial Court and the Administrative Court.
        'COM' => ['Departamenti për Çështje Ekonomike', 'Department for Commercial Matters', []],
        'ADM' => ['Departamenti për Çështje Administrative', 'Department for Administrative Matters', []],
    ];

    /** Departments of the Basic Court of Prishtina (the only one with the Special Department). */
    private const PRISHTINA_DEPARTMENTS = ['GEN', 'JUV', 'SER', 'SPE'];

    /** Departments of the other six basic courts. */
    private const BASIC_COURT_DEPARTMENTS = ['GEN', 'JUV', 'SER'];

    /** Departments of every branch of a basic court. */
    private const BRANCH_DEPARTMENTS = ['GEN'];

    public function sync(): void
    {
        $departments = $this->departments();

        foreach (self::BASIC_COURTS as $code => [$sq, $en, $city, $branches]) {
            $court = $this->upsert(Court::class, ['code' => $code], [
                'name_sq' => "Gjykata Themelore e {$sq}",
                'name_en' => "Basic Court of {$en}",
                'level' => CourtLevel::Basic,
                'city' => $city,
            ]);

            $this->attachDepartments($court, $departments, $code === 'GJTH-PR' ? self::PRISHTINA_DEPARTMENTS : self::BASIC_COURT_DEPARTMENTS);
            $this->renameBranches($court);

            foreach ($branches as $branchSq => $branchEn) {
                $branch = $this->upsert(CourtBranch::class, ['court_id' => $court->id, 'name_sq' => "Dega në {$branchSq}"], [
                    'name_en' => "{$branchEn} branch",
                    'city' => $branchSq,
                ]);

                $this->attachDepartments($branch, $departments, self::BRANCH_DEPARTMENTS);
            }
        }

        foreach (self::OTHER_COURTS as $code => [$sq, $en, $level, $chambers]) {
            $court = $this->upsert(Court::class, ['code' => $code], [
                'name_sq' => $sq,
                'name_en' => $en,
                'level' => $level,
                'city' => 'Prishtina',
            ]);

            foreach ($chambers as $chamberSq => $chamberEn) {
                // No city: a chamber is not a place, and the legacy importer recognises branches by their town.
                $this->upsert(CourtBranch::class, ['court_id' => $court->id, 'name_sq' => $chamberSq], [
                    'name_en' => $chamberEn,
                ]);
            }
        }
    }

    /**
     * @return array<string, Department> by code
     */
    private function departments(): array
    {
        $departments = [];

        foreach (self::DEPARTMENTS as $code => [$sq, $en, $divisions]) {
            $department = $this->upsert(Department::class, ['code' => $code], ['name_sq' => $sq, 'name_en' => $en]);

            foreach ($divisions as [$divisionSq, $divisionEn]) {
                $this->upsert(Division::class, ['department_id' => $department->id, 'name_sq' => $divisionSq], ['name_en' => $divisionEn]);
            }

            $departments[$code] = $department;
        }

        return $departments;
    }

    /**
     * Sets the departments of a court or branch that has none yet (a list edited in the staff panel is kept).
     *
     * @param  array<string, Department>  $departments
     * @param  list<string>  $codes
     */
    private function attachDepartments(Court|CourtBranch $owner, array $departments, array $codes): void
    {
        if ($owner->departments()->exists()) {
            return;
        }

        $owner->departments()->attach(array_map(fn (string $code): int => $departments[$code]->id, $codes));
    }

    private function renameBranches(Court $court): void
    {
        foreach (self::RENAMED_BRANCHES as $old => $new) {
            $branch = CourtBranch::withTrashed()->where('court_id', $court->id)->where('name_sq', $old)->first();

            if ($branch === null || CourtBranch::withTrashed()->where('court_id', $court->id)->where('name_sq', $new)->exists()) {
                continue;
            }

            $branch->update(['name_sq' => $new]);
        }
    }

    /**
     * @template T of ReferenceModel
     *
     * @param  class-string<T>  $model
     * @param  array<string, mixed>  $match
     * @param  array<string, mixed>  $values
     * @return T
     */
    private function upsert(string $model, array $match, array $values): ReferenceModel
    {
        /** @var T $record */
        $record = $model::withTrashed()->firstOrNew($match);
        $record->fill($values);

        if (! $record->exists) {
            $record->is_active = true;
        }

        $record->save();

        return $record;
    }
}
