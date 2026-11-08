<?php

namespace Database\Seeders;

use App\Enums\CourtLevel;
use App\Enums\ProsecutionLevel;
use App\Models\Reference\Applicant;
use App\Models\Reference\Category;
use App\Models\Reference\Court;
use App\Models\Reference\CourtBranch;
use App\Models\Reference\CriminalCode;
use App\Models\Reference\CriminalOffence;
use App\Models\Reference\DefendantPosition;
use App\Models\Reference\Department;
use App\Models\Reference\Division;
use App\Models\Reference\EconomicStatus;
use App\Models\Reference\Nationality;
use App\Models\Reference\ProsecutionOffice;
use App\Models\Reference\ReferenceModel;
use App\Models\Reference\SecurityMeasureType;
use Illuminate\Database\Seeder;

/**
 * Public, non-personal reference structure (safe for production):
 * the 7 basic courts and their branches, the Court of Appeals and the Supreme Court,
 * court departments, prosecution offices, criminal codes and a SAMPLE of criminal-code
 * articles. Editors can correct or extend everything in the staff panel or by CSV import.
 * Idempotent: rows are matched by code / Albanian name.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->courts();
        $this->departments();
        $this->prosecutionOffices();
        $this->criminalCodes();
        $this->simpleLists();
    }

    private function courts(): void
    {
        $basic = [
            'GJTH-PR' => ['Prishtinës', 'Prishtina', 'Prishtina', ['Podujevë' => 'Podujevo', 'Lipjan' => 'Lipljan', 'Drenas' => 'Drenas', 'Graçanicë' => 'Gračanica']],
            'GJTH-PZ' => ['Prizrenit', 'Prizren', 'Prizren', ['Dragash' => 'Dragash', 'Suharekë' => 'Suhareka']],
            'GJTH-PE' => ['Pejës', 'Peja', 'Peja', ['Deçan' => 'Deçan', 'Istog' => 'Istog', 'Klinë' => 'Klina']],
            'GJTH-GJK' => ['Gjakovës', 'Gjakova', 'Gjakova', ['Malishevë' => 'Malisheva', 'Rahovec' => 'Rahovec']],
            'GJTH-MI' => ['Mitrovicës', 'Mitrovica', 'Mitrovica', ['Skenderaj' => 'Skenderaj', 'Vushtrri' => 'Vushtrri', 'Leposaviq' => 'Leposavić', 'Zubin Potok' => 'Zubin Potok']],
            'GJTH-FE' => ['Ferizajt', 'Ferizaj', 'Ferizaj', ['Kaçanik' => 'Kaçanik', 'Shtërpcë' => 'Štrpce']],
            'GJTH-GJ' => ['Gjilanit', 'Gjilan', 'Gjilan', ['Kamenicë' => 'Kamenica', 'Viti' => 'Vitia']],
        ];

        foreach ($basic as $code => [$sq, $en, $city, $branches]) {
            $court = $this->upsert(Court::class, ['code' => $code], [
                'name_sq' => "Gjykata Themelore e {$sq}",
                'name_en' => "Basic Court of {$en}",
                'level' => CourtLevel::Basic,
                'city' => $city,
            ]);

            foreach ($branches as $branchSq => $branchEn) {
                $this->upsert(CourtBranch::class, ['court_id' => $court->id, 'name_sq' => "Dega në {$branchSq}"], [
                    'name_en' => "{$branchEn} branch",
                    'city' => $branchSq,
                ]);
            }
        }

        $this->upsert(Court::class, ['code' => 'GJA'], [
            'name_sq' => 'Gjykata e Apelit',
            'name_en' => 'Court of Appeals',
            'level' => CourtLevel::Appeal,
            'city' => 'Prishtina',
        ]);

        $this->upsert(Court::class, ['code' => 'GJS'], [
            'name_sq' => 'Gjykata Supreme',
            'name_en' => 'Supreme Court',
            'level' => CourtLevel::Supreme,
            'city' => 'Prishtina',
        ]);
    }

    private function departments(): void
    {
        $departments = [
            'GEN' => ['Departamenti i Përgjithshëm', 'General Department', [
                ['Divizioni Penal', 'Criminal Division'],
                ['Divizioni Civil', 'Civil Division'],
                ['Divizioni për Kundërvajtje', 'Minor Offences Division'],
            ]],
            'SER' => ['Departamenti për Krime të Rënda', 'Serious Crimes Department', []],
            'SPE' => ['Departamenti Special', 'Special Department', []],
            'JUV' => ['Departamenti për të Mitur', 'Juvenile Department', []],
            'COM' => ['Departamenti për Çështje Ekonomike', 'Department for Commercial Matters', []],
            'ADM' => ['Departamenti për Çështje Administrative', 'Department for Administrative Matters', []],
        ];

        foreach ($departments as $code => [$sq, $en, $divisions]) {
            $department = $this->upsert(Department::class, ['code' => $code], ['name_sq' => $sq, 'name_en' => $en]);

            foreach ($divisions as [$divisionSq, $divisionEn]) {
                $this->upsert(Division::class, ['department_id' => $department->id, 'name_sq' => $divisionSq], ['name_en' => $divisionEn]);
            }
        }
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
     * Criminal codes and a sample of articles. Article numbers and titles are public law;
     * this is a starting sample — editors should verify and complete the list (CSV import).
     */
    private function criminalCodes(): void
    {
        $categories = [];

        foreach ([
            'corruption' => ['Korrupsion', 'Corruption'],
            'organized' => ['Krim i organizuar', 'Organised crime'],
            'economic' => ['Krime ekonomike', 'Economic crimes'],
            'violent' => ['Krime të dhunshme', 'Violent crimes'],
            'domestic' => ['Dhunë në familje', 'Domestic violence'],
            'sexual' => ['Krime seksuale', 'Sexual offences'],
            'narcotics' => ['Narkotikë', 'Narcotics'],
            'trafficking' => ['Trafikim', 'Trafficking'],
            'war' => ['Krime lufte', 'War crimes'],
            'terrorism' => ['Terrorizëm', 'Terrorism'],
            'weapons' => ['Armë', 'Weapons'],
            'property' => ['Krime pronësore', 'Property crimes'],
            'other' => ['Tjetër', 'Other'],
        ] as $key => [$sq, $en]) {
            $categories[$key] = $this->upsert(Category::class, ['name_sq' => $sq], ['name_en' => $en])->id;
        }

        $codes = [
            'KPRK-2019' => ['Kodi Penal i Republikës së Kosovës (Kodi nr. 06/L-074)', 'Criminal Code of the Republic of Kosovo (Code No. 06/L-074)', 2019],
            'KPK-2012' => ['Kodi Penal i Republikës së Kosovës (Kodi nr. 04/L-082)', 'Criminal Code of the Republic of Kosovo (Code No. 04/L-082)', 2012],
            'UNMIK-2003/25' => ['Kodi i Përkohshëm Penal i Kosovës (Rregullorja e UNMIK-ut 2003/25)', 'Provisional Criminal Code of Kosovo (UNMIK Regulation 2003/25)', 2003],
        ];

        $codeIds = [];

        foreach ($codes as $code => [$sq, $en, $year]) {
            $codeIds[$code] = $this->upsert(CriminalCode::class, ['code' => $code], ['name_sq' => $sq, 'name_en' => $en, 'year' => $year])->id;
        }

        $corruptionChapterSq = 'Korrupsioni zyrtar dhe veprat penale kundër detyrës zyrtare';
        $corruptionChapterEn = 'Official corruption and criminal offences against official duty';

        $articles = [
            'KPRK-2019' => [
                ['414', 'Keqpërdorimi i pozitës ose autoritetit zyrtar', 'Abuse of official position or authority', 'corruption'],
                ['415', 'Përvetësimi në detyrë', 'Misappropriation in office', 'corruption'],
                ['416', 'Mashtrimi në detyrë', 'Fraud in office', 'corruption'],
                ['421', 'Marrja e ryshfetit', 'Accepting bribes', 'corruption'],
                ['422', 'Dhënia e ryshfetit', 'Giving bribes', 'corruption'],
                ['423', 'Ushtrimi i ndikimit', 'Trading in influence', 'corruption'],
                ['426', 'Falsifikimi i dokumenteve zyrtare', 'Falsifying official documents', 'corruption'],
                ['172', 'Vrasja', 'Murder', 'violent'],
                ['173', 'Vrasja e rëndë', 'Aggravated murder', 'violent'],
                ['248', 'Dhuna në familje', 'Domestic violence', 'domestic'],
                ['283', 'Krimi i organizuar', 'Organised crime', 'organized'],
            ],
            'KPK-2012' => [
                ['422', 'Keqpërdorimi i pozitës ose autoritetit zyrtar', 'Abuse of official position or authority', 'corruption'],
                ['423', 'Përvetësimi në detyrë', 'Misappropriation in office', 'corruption'],
                ['424', 'Mashtrimi në detyrë', 'Fraud in office', 'corruption'],
                ['428', 'Marrja e ryshfetit', 'Accepting bribes', 'corruption'],
                ['429', 'Dhënia e ryshfetit', 'Giving bribes', 'corruption'],
                ['430', 'Ushtrimi i ndikimit', 'Trading in influence', 'corruption'],
                ['434', 'Falsifikimi i dokumenteve zyrtare', 'Falsifying official documents', 'corruption'],
                ['283', 'Krimi i organizuar', 'Organised crime', 'organized'],
            ],
            'UNMIK-2003/25' => [
                ['339', 'Keqpërdorimi i pozitës zyrtare ose i autorizimeve', 'Abuse of official position or authority', 'corruption'],
                ['340', 'Përvetësimi gjatë ushtrimit të detyrës', 'Misappropriation in exercising duty', 'corruption'],
                ['341', 'Mashtrimi në detyrë', 'Fraud in office', 'corruption'],
                ['343', 'Marrja e ryshfetit', 'Accepting bribes', 'corruption'],
                ['344', 'Dhënia e ryshfetit', 'Giving bribes', 'corruption'],
                ['345', 'Ushtrimi i ndikimit', 'Trading in influence', 'corruption'],
                ['146', 'Vrasja', 'Murder', 'violent'],
                ['147', 'Vrasja e rëndë', 'Aggravated murder', 'violent'],
            ],
        ];

        foreach ($articles as $code => $rows) {
            foreach ($rows as [$article, $sq, $en, $category]) {
                $this->upsert(CriminalOffence::class, ['criminal_code_id' => $codeIds[$code], 'article_number' => $article], [
                    'title_sq' => $sq,
                    'title_en' => $en,
                    'chapter' => $category === 'corruption' ? $corruptionChapterSq.' / '.$corruptionChapterEn : null,
                    'category_id' => $categories[$category],
                ]);
            }
        }
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
