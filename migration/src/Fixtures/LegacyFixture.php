<?php

namespace Migration\Fixtures;

use Database\Faker\KosovoProvider;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Migration\Import\ModuleConfig;

/**
 * A FAKE legacy database for tests and trial runs: the legacy tables exactly as
 * config('legacy.modules') describes them, filled with flat rows (one per hearing and
 * defendant) full of the problems the importer must handle — spelling variants and
 * duplicates in reference values, broken encoding, free text instead of codes, invalid
 * dates, unknown courts and articles, empty rows and columns the mapping does not know.
 *
 * Every name is invented ("Testaj", "Provaj", "Shembulli" are not real surnames) or
 * produced by Faker. No real data is ever used.
 */
final class LegacyFixture
{
    public const MONITOR_NAME = 'Monitor Provaj';

    public const MONITOR_EMAIL = 'monitor.provaj@courtwatch.test';

    /** Extra legacy column that the mapping does not read. */
    public const UNMAPPED_COLUMN = 'fusha_vjeter';

    /** @var array<string, list<array<string, string|null>>> module => rows (field => value) */
    private array $rows = [];

    /** @var array<string, array{cases: int, hearings: int, parties: int}> */
    private array $expected = [];

    private readonly Generator $faker;

    private readonly KosovoProvider $kosovo;

    public function __construct(private readonly string $connection, int $seed = 2026)
    {
        $this->faker = Faker::create('sq_AL');
        $this->faker->seed($seed);
        $this->kosovo = new KosovoProvider($this->faker);
    }

    /**
     * Creates (or recreates) the legacy tables and fills them.
     *
     * @return array<string, array{rows: int, cases: int, hearings: int, parties: int}> what a complete import must produce, per module
     */
    public function create(int $fillerCasesPerModule = 4): array
    {
        $modules = ModuleConfig::all();
        $this->scenarios();

        foreach (array_keys($modules) as $key) {
            for ($i = 1; $i <= $fillerCasesPerModule; $i++) {
                $this->filler($modules[$key], $i);
            }
        }

        $result = [];

        foreach ($modules as $key => $module) {
            $this->createTable($module);
            $this->insert($module, $this->rows[$key] ?? []);

            $result[$key] = [
                'rows' => count($this->rows[$key] ?? []),
                ...($this->expected[$key] ?? ['cases' => 0, 'hearings' => 0, 'parties' => 0]),
            ];
        }

        return $result;
    }

    /**
     * Appends rows to a legacy table after the first import (to test re-runs).
     *
     * @param  list<array<string, string|null>>  $rows  field => value
     */
    public function append(string $module, array $rows): void
    {
        $this->insert(ModuleConfig::all([$module])[$module], $rows);
    }

    // ---------------------------------------------------------------- hand-written scenarios

    private function scenarios(): void
    {
        $c = 'criminal_corruption';

        // Case 1 — registered at the prosecution stage (no court number yet), then tried:
        // two defendants, three hearings, spelling variants everywhere.
        $this->add($c, [
            'indictment_number' => 'PP/II.nr. 55/2020', 'court' => 'Gjykata Themelore Prishtinë', 'department' => 'Departamenti për Krime të Rënda',
            'prosecutor' => 'Drita Fiktive', 'prosecution_office' => 'Prokuroria Themelore në Prishtinë',
            'party_name' => 'Agim Testaj', 'party_gender' => 'M', 'article' => 'Neni 414', 'criminal_code' => 'KPRK',
            'stage.criminal_report_filed.date' => '10.02.2020', 'created_at' => '2020-02-11 09:00:00',
        ]);
        $this->add($c, [
            'court_case_number' => 'P.nr. 101/2021', 'indictment_number' => 'PP/II.nr.55/2020', 'court' => 'GJYKATA THEMELORE PRISHTINË',
            'judge' => 'Besnik Shembulli', 'prosecutor' => 'drita  fiktive',
            'party_name' => 'Agim Testaj', 'party_gender' => 'Mashkull', 'party_nationality' => 'Shqiptare', 'party_counsel' => 'i autorizuar',
            'article' => '414', 'criminal_code' => 'KPRK', 'stage.indictment_filed.date' => '2021-01-20',
            'hearing_date' => '2021-03-15', 'hearing_type' => 'Seanca fillestare', 'is_public' => 'Po', 'was_postponed' => 'po',
            'postponement_reasons' => 'Mungesa e prokurorit; mungesa e të akuzuarit', 'recording' => 'Audio',
            'monitor' => self::MONITOR_NAME, 'participants' => 'Gjyqtari, Prokurori, I akuzuari',
        ]);
        $this->add($c, [
            'court_case_number' => 'P.nr. 101/2021', 'court' => 'Gjykata Themelore Prishtinë', 'judge' => 'BESNIK SHEMBULLI',
            'party_name' => 'Mira Provaj', 'party_gender' => 'F', 'party_nationality' => 'Shqiptar', 'article' => 'neni 415 par. 1',
            'hearing_date' => '15.03.2021', 'hearing_type' => 'Seanca fillestare', 'monitor' => self::MONITOR_NAME,
        ]);
        $this->add($c, [
            'court_case_number' => 'P nr 101/2021', 'court' => 'Gjykata Themelore PrishtinÃ«', 'judge' => 'Shembulli Besnik',
            'party_name' => 'Testaj Agim', 'hearing_date' => '2021-05-10', 'hearing_type' => 'Shqyrtimi gjyqësor', 'is_public' => 'publike',
            'started_late' => 'po', 'delay_minutes' => '45 min', 'delay_cause' => 'Gjyqtari', 'was_postponed' => 'jo', 'recording' => 'audio dhe video',
            'notes' => 'I pandehuri Agim Testaj u paraqit me vonesë.', 'monitor' => self::MONITOR_NAME, self::UNMAPPED_COLUMN => 'vlerë e vjetër',
        ]);
        $this->add($c, [
            'court_case_number' => 'P.nr. 101/2021', 'court' => 'Gjykata Themelore Prishtinë', 'party_name' => 'Agim Testaj',
            'hearing_date' => '2021-09-01', 'hearing_type' => 'Shpallja e aktgjykimit',
            'stage.judgment_announced.date' => '01.09.2021', 'stage.judgment_announced.outcome' => 'Dënues',
            'sentence_prison_months' => '24', 'sentence_fine' => '1.500,00 €', 'seizure_requested' => 'po', 'seizure_granted' => 'jo',
        ]);
        $this->add($c, [
            'court_case_number' => 'P.nr. 101/2021', 'court' => 'Gjykata Themelore Prishtinë', 'party_name' => 'Mira Provaj',
            'hearing_date' => '2021-09-01', 'stage.judgment_announced.outcome' => 'Lirues',
        ]);
        $this->expect($c, cases: 1, hearings: 3, parties: 2);

        // Case 2 — values the importer cannot map: they must end up in review, not be dropped.
        $this->add($c, [
            'court_case_number' => 'P.nr. 202/2022', 'court' => 'Gjykata e Hënës', 'party_name' => 'Kompania Fiktive SH.P.K.',
            'article' => '999', 'criminal_code' => 'KPRK', 'hearing_date' => '2022-02-30', 'hearing_type' => 'Fjala përfundimtare',
        ]);
        $this->add($c, [
            'court_case_number' => 'P.nr. 202/2022', 'court' => 'Gjykata e Hënës', 'party_name' => 'Kompania Fiktive SH.P.K.',
            'party_nationality' => 'Marsian', 'hearing_date' => '2022-03-01', 'hearing_type' => 'seancë e çuditshme',
            'was_postponed' => 'po', 'postponement_reasons' => 'pa arsye', 'monitor' => 'Monitor I Panjohur',
        ]);
        $this->expect($c, cases: 1, hearings: 1, parties: 1);

        // Case 3 — a branch of a court, a department alias, stage outcomes and a chronology problem.
        $this->add($c, [
            'court_case_number' => 'P.nr. 303/2023', 'court' => 'Gjykata Themelore Prishtinë - Dega Podujevë', 'department' => 'DKR',
            'judge' => 'besnik   shembulli', 'party_name' => 'Driton Provaj', 'party_gender' => 'm',
            'stage.indictment_filed.date' => '2023-05-01', 'stage.indictment_decision.date' => '2023-03-01',
            'stage.indictment_decision.outcome' => 'e konfirmuar', 'stage.plea_agreement.outcome' => 'po',
            'hearing_date' => '2023-04-04', 'hearing_type' => 'seanca e dyte', 'venue' => 'zyrë', 'is_public' => 'e mbyllur',
            'interpretation_needed' => 'po', 'interpretation_provided' => 'jo',
        ]);
        $this->expect($c, cases: 1, hearings: 1, parties: 1);

        // An empty row.
        $this->add($c, []);

        $this->add('civil', [
            'court_case_number' => 'C.nr. 12/2022', 'court' => 'Gjykata Themelore Gjilan', 'plaintiff_name' => 'Arta Testaj', 'respondent_name' => 'Kompania Provë SH.A.',
            'claim_type' => 'Pronësore', 'claim_value' => '12.500', 'filed_date' => '2022-01-10', 'plaintiff_type' => 'person fizik', 'respondent_type' => 'person juridik',
            'representation' => 'avokat', 'legal_aid' => 'jo', 'mediation_attempted' => 'po', 'civil_outcome' => 'Aprovohet', 'decision_date' => '2022-11-20',
            'hearing_date' => '2022-03-10', 'hearing_type' => 'shqyrtimi gjyqesor', 'was_postponed' => 'po', 'postponement_reasons' => 'mungesa e mbrojtesit',
        ]);
        $this->add('civil', [
            'court_case_number' => 'C.nr. 12/2022', 'court' => 'Gjykata Themelore Gjilan', 'plaintiff_name' => 'Arta Testaj', 'respondent_name' => 'Kompania Provë SH.A.',
            'hearing_date' => '2022-11-20', 'hearing_type' => 'shpallja e aktgjykimit',
        ]);
        $this->expect('civil', cases: 1, hearings: 2, parties: 2);

        $this->add('administrative', [
            'court_case_number' => 'A.nr. 7/2021', 'court' => 'Gjykata Themelore e Prishtinës', 'department' => 'Departamenti për Çështje Administrative',
            'plaintiff_name' => 'Blerta Provaj', 'plaintiff_type' => 'person fizik', 'respondent_body' => 'Ministria Fiktive',
            'first_instance_decision' => 'Refuzim i kërkesës', 'first_instance_decision_date' => '2020-12-01', 'decision_reasoned' => 'jo',
            'legal_remedy_type' => 'ankesë', 'legal_remedy_date' => '2021-01-05', 'second_instance_outcome' => 'Refuzohet',
            'second_instance_decision_date' => '2021-03-01', 'conflict_filed' => 'po', 'conflict_filed_date' => '2021-04-01', 'representation' => 'vete',
            'hearing_date' => '2021-06-15', 'hearing_type' => 'shqyrtimi gjyqesor',
        ]);
        $this->expect('administrative', cases: 1, hearings: 1, parties: 1);
    }

    // ---------------------------------------------------------------- generated filler

    private function filler(ModuleConfig $module, int $index): void
    {
        $courts = ['Gjykata Themelore e Prishtinës', 'Gjykata Themelore Prizren', 'Gjykata Themelore Pejë', 'Gjykata Themelore Gjakovë', 'Gjykata Themelore Mitrovicë', 'Gjykata Themelore Ferizaj', 'Gjykata Themelore Gjilan'];
        $types = ['Seanca fillestare', 'Shqyrtimi gjyqësor', 'Fjala përfundimtare', 'Shpallja e aktgjykimit', 'seanca e dyte'];
        $reasons = ['Mungesa e prokurorit', 'Mungesa e mbrojtësit', 'Mungesa e të akuzuarit', 'Mungesa e ekspertit'];
        $yesNo = ['po', 'jo', 'Po', 'JO', '1', '0'];

        $year = 2020 + ($index % 4);
        $court = (string) $this->faker->randomElement($courts);
        $judge = $this->kosovo->kosovoName();
        $number = sprintf('%s.nr. %d/%d', $module->isCriminal() ? 'P' : 'C', 1000 + $index, $year);
        $hearings = $this->faker->numberBetween(1, 3);
        $people = $module->isCriminal() ? $this->faker->numberBetween(1, 2) : 1;

        $names = [];

        while (count($names) < $people) {
            // Each defendant keeps one article across all of their rows.
            $names[$this->kosovo->kosovoName()] = (string) $this->faker->randomElement(['414', '415', '421', '422']);
        }

        $date = $this->faker->dateTimeBetween("{$year}-01-01", "{$year}-06-30");

        for ($h = 0; $h < $hearings; $h++) {
            $date = (clone $date)->modify('+'.$this->faker->numberBetween(20, 90).' days');
            $postponed = $this->faker->boolean(35);

            foreach ($names as $name => $article) {
                $row = [
                    'court_case_number' => $number,
                    'court' => $court,
                    'judge' => $this->faker->boolean(20) ? mb_strtoupper($judge) : $judge,
                    'hearing_date' => $this->faker->boolean() ? $date->format('Y-m-d') : $date->format('d.m.Y'),
                    'hearing_type' => (string) $this->faker->randomElement($types),
                    'is_public' => (string) $this->faker->randomElement($yesNo),
                    'was_postponed' => $postponed ? 'po' : 'jo',
                    'postponement_reasons' => $postponed ? (string) $this->faker->randomElement($reasons) : null,
                    'recording' => (string) $this->faker->randomElement(['Audio', 'jo', 'video']),
                    'started_late' => 'jo',
                ];

                if ($module->isCriminal()) {
                    $row += [
                        'party_name' => (string) $name,
                        'party_gender' => (string) $this->faker->randomElement(['M', 'F', 'Mashkull', 'Femër']),
                        'article' => $article,
                        'criminal_code' => 'KPRK',
                        'stage.indictment_filed.date' => "{$year}-01-01",
                    ];
                } else {
                    $row += ['plaintiff_name' => (string) $name, 'respondent_name' => $this->kosovo->kosovoCompany()];
                }

                $this->add($module->key, $row);
            }

            // Civil-type rows have a different respondent per row in this generator: keep one.
            if (! $module->isCriminal()) {
                break;
            }
        }

        $parties = $module->isCriminal() ? $people : 1 + ($module->column('respondent_name') !== null ? 1 : 0);
        $this->expect($module->key, cases: 1, hearings: $module->isCriminal() ? $hearings : 1, parties: $parties);
    }

    // ---------------------------------------------------------------- helpers

    /**
     * @param  array<string, string|null>  $fields
     */
    private function add(string $module, array $fields): void
    {
        $this->rows[$module][] = $fields;
    }

    private function expect(string $module, int $cases, int $hearings, int $parties): void
    {
        $current = $this->expected[$module] ?? ['cases' => 0, 'hearings' => 0, 'parties' => 0];

        $this->expected[$module] = [
            'cases' => $current['cases'] + $cases,
            'hearings' => $current['hearings'] + $hearings,
            'parties' => $current['parties'] + $parties,
        ];
    }

    private function createTable(ModuleConfig $module): void
    {
        $schema = Schema::connection($this->connection);
        $schema->dropIfExists($module->table);

        $schema->create($module->table, function (Blueprint $table) use ($module): void {
            $table->increments($module->idColumn);

            foreach ($module->mappedColumns() as $column) {
                if ($column !== $module->idColumn) {
                    $table->text($column)->nullable();
                }
            }

            $table->text(self::UNMAPPED_COLUMN)->nullable();
            $table->text('created_at')->nullable();
            $table->text('updated_at')->nullable();
        });
    }

    /**
     * @param  list<array<string, string|null>>  $rows  field => value
     */
    private function insert(ModuleConfig $module, array $rows): void
    {
        foreach ($rows as $fields) {
            $record = [];

            foreach ($fields as $field => $value) {
                $column = in_array($field, [self::UNMAPPED_COLUMN, 'created_at', 'updated_at'], true) ? $field : $module->column($field);

                if ($column !== null) {
                    $record[$column] = $value;
                }
            }

            DB::connection($this->connection)->table($module->table)->insert($record === [] ? [self::UNMAPPED_COLUMN => null] : $record);
        }
    }
}
