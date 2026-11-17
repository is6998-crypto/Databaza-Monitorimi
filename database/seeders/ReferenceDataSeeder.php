<?php

namespace Database\Seeders;

use App\Enums\ProsecutionLevel;
use App\Models\Reference\Applicant;
use App\Models\Reference\DefendantPosition;
use App\Models\Reference\EconomicStatus;
use App\Models\Reference\Nationality;
use App\Models\Reference\ProsecutionOffice;
use App\Models\Reference\ReferenceModel;
use App\Models\Reference\SecurityMeasureType;
use App\Services\Reference\CourtStructure;
use App\Services\Reference\OffenceCatalogue;
use Illuminate\Database\Seeder;

/**
 * Public, non-personal reference structure (safe for production):
 * every court with its branches, chambers and departments (App\Services\Reference\CourtStructure),
 * prosecution offices, criminal codes with the full list of criminal offences
 * and their statistics categories. Editors can correct or extend everything in the staff panel or by CSV import.
 * Idempotent: rows are matched by code / Albanian name.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        (new CourtStructure)->sync();
        $this->prosecutionOffices();
        $this->criminalCodes();
        $this->simpleLists();
    }

    private function prosecutionOffices(): void
    {
        foreach (['Prishtinë' => 'Prishtina', 'Prizren' => 'Prizren', 'Pejë' => 'Peja', 'Gjakovë' => 'Gjakova', 'Mitrovicë' => 'Mitrovica', 'Ferizaj' => 'Ferizaj', 'Gjilan' => 'Gjilan'] as $sq => $en) {
            $this->upsert(ProsecutionOffice::class, ['name_sq' => "Prokuroria Themelore në {$sq}"], [
                'name_en' => "Basic Prosecution Office in {$en}",
                'level' => ProsecutionLevel::Basic,
                'city' => $en,
            ]);
        }

        $this->upsert(ProsecutionOffice::class, ['name_sq' => 'Prokuroria Speciale e Republikës së Kosovës'], [
            'name_en' => 'Special Prosecution Office of the Republic of Kosovo',
            'level' => ProsecutionLevel::Special,
            'city' => 'Prishtina',
        ]);

        $this->upsert(ProsecutionOffice::class, ['name_sq' => 'Prokuroria e Apelit'], [
            'name_en' => 'Appellate Prosecution Office',
            'level' => ProsecutionLevel::Appellate,
            'city' => 'Prishtina',
        ]);

        $this->upsert(ProsecutionOffice::class, ['name_sq' => 'Zyra e Kryeprokurorit të Shtetit'], [
            'name_en' => 'Office of the Chief State Prosecutor',
            'level' => ProsecutionLevel::State,
            'city' => 'Prishtina',
        ]);
    }

    /**
     * Criminal codes, the full list of criminal offences and the statistics categories they are sorted into
     * (database/data, see App\Services\Reference\OffenceCatalogue). Article numbers and titles are public law.
     */
    private function criminalCodes(): void
    {
        (new OffenceCatalogue)->sync();
    }

    private function simpleLists(): void
    {
        $lists = [
            Nationality::class => [
                ['Shqiptar', 'Albanian'], ['Serb', 'Serb'], ['Boshnjak', 'Bosniak'], ['Turk', 'Turkish'],
                ['Rom', 'Roma'], ['Ashkali', 'Ashkali'], ['Egjiptian', 'Egyptian'], ['Goran', 'Gorani'],
                ['Malazez', 'Montenegrin'], ['Kroat', 'Croat'], ['Tjetër / i huaj', 'Other / foreign'],
            ],
            DefendantPosition::class => [
                ['Zyrtar i lartë publik', 'Senior public official'], ['Ministër / zëvendësministër', 'Minister / deputy minister'],
                ['Kryetar komune', 'Mayor'], ['Zyrtar komunal', 'Municipal official'], ['Drejtor i ndërmarrjes publike', 'Director of a public enterprise'],
                ['Gjyqtar', 'Judge'], ['Prokuror', 'Prosecutor'], ['Zyrtar policor', 'Police officer'], ['Zyrtar doganor', 'Customs officer'],
                ['Shërbyes civil', 'Civil servant'], ['Pronar biznesi', 'Business owner'], ['Person privat', 'Private individual'], ['Tjetër', 'Other'],
            ],
            EconomicStatus::class => [
                ['I punësuar', 'Employed'], ['I papunë', 'Unemployed'], ['I vetëpunësuar', 'Self-employed'], ['Student', 'Student'],
                ['Pensionist', 'Retired'], ['E panjohur', 'Unknown'],
            ],
            SecurityMeasureType::class => [
                ['Ftesa', 'Summons'], ['Urdhëresa për arrestim', 'Order for arrest'],
                ['Premtimi për të mos e lëshuar vendbanimin', 'Promise not to leave place of residence'],
                ['Ndalimi i afrimit në vend ose person të caktuar', 'Prohibition on approaching a place or person'],
                ['Paraqitja në stacionin policor', 'Reporting to a police station'], ['Dorëzania', 'Bail'],
                ['Arresti shtëpiak', 'House detention'], ['Paraburgimi', 'Detention on remand'], ['Të tjera', 'Other'],
            ],
            Applicant::class => [
                ['Policia e Kosovës', 'Kosovo Police'], ['Agjencia Kundër Korrupsionit', 'Anti-Corruption Agency'],
                ['Dogana e Kosovës', 'Kosovo Customs'], ['Administrata Tatimore e Kosovës', 'Tax Administration of Kosovo'],
                ['Njësia e Inteligjencës Financiare', 'Financial Intelligence Unit'], ['Zyra Kombëtare e Auditimit', 'National Audit Office'],
                ['Pala e dëmtuar', 'Injured party'], ['Qytetar', 'Citizen'], ['Prokurori sipas detyrës zyrtare', 'Prosecutor ex officio'],
                ['Institucion tjetër', 'Other institution'],
            ],
        ];

        foreach ($lists as $model => $rows) {
            foreach ($rows as [$sq, $en]) {
                $this->upsert($model, ['name_sq' => $sq], ['name_en' => $en]);
            }
        }
    }

    /**
     * @template T of \App\Models\Reference\ReferenceModel
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
