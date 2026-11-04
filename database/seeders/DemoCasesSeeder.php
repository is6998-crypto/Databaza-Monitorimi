<?php

namespace Database\Seeders;

use App\Enums\Appellant;
use App\Enums\CaseType;
use App\Enums\ChargeStage;
use App\Enums\CivilOutcome;
use App\Enums\ClaimType;
use App\Enums\CounselType;
use App\Enums\DelayCause;
use App\Enums\Gender;
use App\Enums\HearingType;
use App\Enums\Instance;
use App\Enums\LegalRemedyType;
use App\Enums\MeasureKind;
use App\Enums\Participant;
use App\Enums\PartyRole;
use App\Enums\PhoneUser;
use App\Enums\PlaintiffType;
use App\Enums\PostponementReason;
use App\Enums\Recording;
use App\Enums\Representation;
use App\Enums\Role;
use App\Enums\SecondInstanceOutcome;
use App\Enums\SentenceKind;
use App\Enums\StageOutcome;
use App\Enums\StageType;
use App\Enums\Venue;
use App\Enums\WorkflowStatus;
use App\Models\AdministrativeCaseDetail;
use App\Models\CaseParty;
use App\Models\CaseStage;
use App\Models\Charge;
use App\Models\CivilCaseDetail;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\Measure;
use App\Models\Reference\Applicant;
use App\Models\Reference\Category;
use App\Models\Reference\Court;
use App\Models\Reference\CriminalOffence;
use App\Models\Reference\DefendantPosition;
use App\Models\Reference\Department;
use App\Models\Reference\EconomicStatus;
use App\Models\Reference\Judge;
use App\Models\Reference\Monitor;
use App\Models\Reference\Nationality;
use App\Models\Reference\ProsecutionOffice;
use App\Models\Reference\Prosecutor;
use App\Models\Reference\SecurityMeasureType;
use App\Models\Sentence;
use App\Models\User;
use App\Models\WorkflowTransition;
use Database\Faker\KosovoProvider;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * FAKE monitored cases for development and demos: parties, charges, a chronological stage
 * timeline, hearings, measures, sentences and review-workflow history. No real people.
 */
class DemoCasesSeeder extends Seeder
{
    private Generator $faker;

    private KosovoProvider $kosovo;

    private Carbon $today;

    /** @var Collection<int, Court> */
    private Collection $courts;

    /** @var Collection<int, Monitor> */
    private Collection $monitors;

    private ?User $editor = null;

    /** @var array<string, Collection<int, CriminalOffence>> */
    private array $offences = [];

    public function run(): void
    {
        if (CourtCase::query()->exists()) {
            return;
        }

        $this->faker = FakerFactory::create();
        $this->faker->seed(20261102);
        $this->kosovo = new KosovoProvider($this->faker);
        $this->today = Carbon::now((string) config('courtwatch.display_timezone'))->startOfDay();
        $this->courts = Court::query()->where('level', 'basic')->orderBy('id')->get();
        $this->monitors = Monitor::query()->with(['courts', 'user'])->get();
        $this->editor = User::query()->whereHas('roles', fn ($query) => $query->where('name', Role::Editor->value))->first();

        $corruption = Category::query()->where('name_en', 'Corruption')->value('id');
        $this->offences = [
            'corruption' => CriminalOffence::query()->where('category_id', $corruption)->get(),
            'other' => CriminalOffence::query()->where('category_id', '!=', $corruption)->get(),
        ];

        activity()->disableLogging();

        $count = (int) config('courtwatch.demo_cases', 200);

        for ($i = 0; $i < $count; $i++) {
            $this->seedCase($i);
        }

        activity()->enableLogging();
    }

    private function seedCase(int $index): void
    {
        $type = $this->pickType();
        $court = $this->pickCourt();
        $monitor = $this->monitorFor($court);
        $openedAt = Carbon::instance($this->faker->dateTimeBetween('-6 years', '-2 months'))->startOfDay();

        $case = new CourtCase;
        $case->fill([
            'type' => $type,
            'court_id' => $court->id,
            'court_branch_id' => $this->faker->boolean(20) ? $court->branches()->inRandomOrder()->value('id') : null,
            'department_id' => $this->departmentFor($type),
            'judge_id' => Judge::query()->where('court_id', $court->id)->inRandomOrder()->value('id'),
            'assigned_monitor_id' => $monitor?->id,
            'court_case_number' => $this->kosovo->courtCaseNumber($type->isCriminal() ? 'PKR' : ($type === CaseType::Administrative ? 'A' : ($type === CaseType::Economic ? 'C.EK' : 'C')), (int) $openedAt->format('Y')),
        ]);

        if ($type->isCriminal()) {
            $office = $this->prosecutionOfficeFor($type, $court);
            $case->fill([
                'prosecution_office_id' => $office?->id,
                'prosecutor_id' => $office ? Prosecutor::query()->where('prosecution_office_id', $office->id)->inRandomOrder()->value('id') : null,
                'indictment_number' => $this->kosovo->indictmentNumber((int) $openedAt->format('Y')),
                'police_case_number' => $this->faker->boolean(70) ? $this->kosovo->policeCaseNumber((int) $openedAt->format('Y')) : null,
            ]);
        }

        $status = $this->pickStatus();
        $case->status = $status;
        $case->created_by = $monitor?->user_id;
        $case->created_at = $openedAt->copy()->setTime(9, 0);
        $case->updated_at = $this->today->copy()->subDays($this->faker->numberBetween(0, 60));
        $case->save();

        $accused = $this->seedParties($case, $type);
        $timeline = $type->isCriminal() ? $this->seedCriminalStages($case, $openedAt) : $this->seedOtherDetails($case, $type, $openedAt);

        if ($type->isCriminal()) {
            $this->seedCharges($case, $accused, $type, $timeline);
            $this->seedMeasures($case, $accused, $type, $timeline);
            $this->seedSentences($case, $accused, $timeline);
        }

        $this->seedHearings($case, $monitor, $timeline, $status, $accused);
        $this->seedWorkflow($case, $status, $monitor);
    }

    private function pickType(): CaseType
    {
        return $this->faker->randomElement([
            ...array_fill(0, 35, CaseType::CriminalCorruption),
            ...array_fill(0, 20, CaseType::CriminalGeneral),
            ...array_fill(0, 18, CaseType::CriminalSerious),
            ...array_fill(0, 9, CaseType::CriminalSpecial),
            ...array_fill(0, 9, CaseType::Civil),
            ...array_fill(0, 6, CaseType::Administrative),
            ...array_fill(0, 3, CaseType::Economic),
        ]);
    }

    private function pickCourt(): Court
    {
        // Prishtina handles the largest share of monitored cases.
        return $this->faker->boolean(35) ? $this->courts->first() : $this->courts->random();
    }

    private function monitorFor(Court $court): ?Monitor
    {
        if ($this->monitors->isEmpty()) {
            return null;
        }

        $matching = $this->monitors->filter(fn (Monitor $monitor): bool => $monitor->courts->contains('id', $court->id));

        return $matching->isNotEmpty() ? $matching->random() : $this->monitors->random();
    }

    private function departmentFor(CaseType $type): ?int
    {
        $code = match ($type) {
            CaseType::CriminalCorruption, CaseType::CriminalSerious => 'SER',
            CaseType::CriminalSpecial => 'SPE',
            CaseType::Administrative => 'ADM',
            CaseType::Economic => 'COM',
            default => 'GEN',
        };

        return Department::query()->where('code', $code)->value('id');
    }

    private function prosecutionOfficeFor(CaseType $type, Court $court): ?ProsecutionOffice
    {
        if ($type === CaseType::CriminalSpecial || ($type === CaseType::CriminalCorruption && $this->faker->boolean(30))) {
            return ProsecutionOffice::query()->where('level', 'special')->first();
        }

        return ProsecutionOffice::query()->where('level', 'basic')->where('city', $court->city)->first()
            ?? ProsecutionOffice::query()->where('level', 'basic')->inRandomOrder()->first();
    }

    private function pickStatus(): WorkflowStatus
    {
        return $this->faker->randomElement([
            ...array_fill(0, 66, WorkflowStatus::Approved),
            ...array_fill(0, 12, WorkflowStatus::Published),
            ...array_fill(0, 8, WorkflowStatus::Submitted),
            ...array_fill(0, 4, WorkflowStatus::InReview),
            ...array_fill(0, 6, WorkflowStatus::Draft),
            ...array_fill(0, 4, WorkflowStatus::Returned),
        ]);
    }

    /**
     * @return Collection<int, CaseParty>
     */
    private function seedParties(CourtCase $case, CaseType $type): Collection
    {
        $accused = collect();
        $nationalities = Nationality::query()->pluck('id', 'name_en');
        $albanian = $nationalities['Albanian'] ?? null;

        if ($type->isCriminal()) {
            foreach (range(1, $this->faker->numberBetween(1, 3)) as $n) {
                $gender = $this->faker->boolean(88) ? Gender::Male : Gender::Female;
                $accused->push($case->parties()->create([
                    'role' => PartyRole::Accused,
                    'name' => $this->kosovo->kosovoName($gender->value),
                    'gender' => $gender,
                    'nationality_id' => $this->faker->boolean(85) ? $albanian : $nationalities->random(),
                    'economic_status_id' => EconomicStatus::query()->inRandomOrder()->value('id'),
                    'defendant_position_id' => $type === CaseType::CriminalCorruption
                        ? DefendantPosition::query()->whereNotIn('name_en', ['Private individual'])->inRandomOrder()->value('id')
                        : DefendantPosition::query()->where('name_en', 'Private individual')->value('id'),
                    'counsel_type' => $this->faker->randomElement([CounselType::Retained, CounselType::Retained, CounselType::ExOfficio, CounselType::None]),
                    'counsel_at_police' => $this->faker->boolean(55),
                ]));
            }

            if ($type === CaseType::CriminalCorruption && $this->faker->boolean(15)) {
                $accused->push($case->parties()->create([
                    'role' => PartyRole::LegalEntity,
                    'name' => $this->kosovo->kosovoCompany(),
                    'gender' => Gender::LegalPerson,
                    'counsel_type' => CounselType::Retained,
                ]));
            }

            if ($this->faker->boolean(45)) {
                $gender = $this->faker->randomElement([Gender::Male, Gender::Female]);
                $case->parties()->create([
                    'role' => PartyRole::InjuredParty,
                    'name' => $this->kosovo->kosovoName($gender->value),
                    'gender' => $gender,
                    'nationality_id' => $albanian,
                ]);
            }

            return $accused;
        }

        $gender = $this->faker->randomElement([Gender::Male, Gender::Female, Gender::LegalPerson]);
        $case->parties()->create([
            'role' => PartyRole::Plaintiff,
            'name' => $gender === Gender::LegalPerson ? $this->kosovo->kosovoCompany() : $this->kosovo->kosovoName($gender->value),
            'gender' => $gender,
            'nationality_id' => $albanian,
        ]);
        $case->parties()->create([
            'role' => PartyRole::Respondent,
            'name' => $type === CaseType::Administrative ? $this->faker->randomElement(['Ministria e Brendshme (demo)', 'Komuna e Prishtinës (demo)', 'Agjencia Kadastrale (demo)', 'Ministria e Arsimit (demo)']) : $this->kosovo->kosovoCompany(),
            'gender' => Gender::LegalPerson,
        ]);

        return $accused;
    }

    /**
     * Builds a chronological stage timeline that stops before today.
     *
     * @return array<string, Carbon> stage value => date
     */
    private function seedCriminalStages(CourtCase $case, Carbon $start): array
    {
        $timeline = [];
        $cursor = $start->copy();

        $add = function (StageType $stage, int $minDays, int $maxDays, array $extra = []) use (&$cursor, &$timeline, $case): bool {
            $date = $cursor->copy()->addDays($this->faker->numberBetween($minDays, $maxDays));

            if ($date->gt($this->today)) {
                return false;
            }

            $cursor = $date;
            $timeline[$stage->value] = $date;
            CaseStage::query()->create(['case_id' => $case->id, 'stage' => $stage, 'date' => $date->toDateString(), ...$extra]);

            return true;
        };

        $add(StageType::CriminalReportFiled, 0, 0, ['applicant_id' => Applicant::query()->inRandomOrder()->value('id')]);
        $direct = $this->faker->boolean(15);

        if (! $direct) {
            if (! $add(StageType::InvestigationOpened, 10, 90)) {
                return $timeline;
            }

            if ($this->faker->boolean(10)) {
                $add(StageType::InvestigationSuspended, 30, 200);

                return $timeline;
            }

            if (! $add(StageType::InvestigationClosed, 60, 420)) {
                return $timeline;
            }
        }

        if (! $add(StageType::IndictmentDrafted, 3, 30, ['prosecutor_id' => $case->prosecutor_id])) {
            return $timeline;
        }

        if (! $add(StageType::IndictmentFiled, 1, 15, ['is_direct_indictment' => $direct])) {
            return $timeline;
        }

        if (! $add(StageType::InitialHearing, 20, 150)) {
            return $timeline;
        }

        if ($this->faker->boolean(12)) {
            $add(StageType::PleaAgreement, 0, 20, ['outcome' => StageOutcome::Accepted]);
            $add(StageType::JudgmentAnnounced, 0, 10, ['outcome' => StageOutcome::Convicting]);
            $add(StageType::JudgmentDrafted, 10, 45, ['outcome' => StageOutcome::Convicting]);

            return $timeline;
        }

        if (! $add(StageType::SecondHearing, 20, 90)) {
            return $timeline;
        }

        $decision = $this->faker->randomElement([StageOutcome::Approved, StageOutcome::Approved, StageOutcome::Approved, StageOutcome::Approved, StageOutcome::Rejected, StageOutcome::Dismissed]);

        if (! $add(StageType::IndictmentDecision, 5, 40, ['outcome' => $decision])) {
            return $timeline;
        }

        if ($decision !== StageOutcome::Approved) {
            return $timeline;
        }

        if (! $add(StageType::MainHearing, 30, 220)) {
            return $timeline;
        }

        $verdict = $this->faker->randomElement([StageOutcome::Convicting, StageOutcome::Convicting, StageOutcome::Convicting, StageOutcome::Acquitting, StageOutcome::Acquitting, StageOutcome::Rejecting, StageOutcome::Mixed]);

        if (! $add(StageType::JudgmentAnnounced, 60, 500, ['outcome' => $verdict])) {
            return $timeline;
        }

        $add(StageType::JudgmentDrafted, 10, 90, ['outcome' => $verdict]);

        if ($this->faker->boolean(55)) {
            $appellant = $verdict === StageOutcome::Convicting ? $this->faker->randomElement([Appellant::Defence, Appellant::Defence, Appellant::BothParties]) : Appellant::Prosecutor;

            if ($add(StageType::AppealFiled, 8, 30, ['appellant' => $appellant])) {
                $add(StageType::AppealDecision, 60, 360, ['outcome' => $this->faker->randomElement([StageOutcome::Confirmed, StageOutcome::Confirmed, StageOutcome::Modified, StageOutcome::Annulled, StageOutcome::ReturnedForRetrial])]);

                if ($this->faker->boolean(12) && $add(StageType::ProtectionOfLegalityRequested, 20, 90, ['appellant' => Appellant::Defence])) {
                    $add(StageType::SupremeCourtDecision, 60, 240, ['outcome' => $this->faker->randomElement([StageOutcome::Rejected, StageOutcome::Rejected, StageOutcome::PartiallyApproved, StageOutcome::Approved])]);
                }
            }
        }

        if (in_array($verdict, [StageOutcome::Convicting, StageOutcome::Mixed], true) && $this->faker->boolean(50)) {
            $add(StageType::JudgmentExecuted, 30, 200);
        }

        return $timeline;
    }

    /**
     * @return array<string, Carbon>
     */
    private function seedOtherDetails(CourtCase $case, CaseType $type, Carbon $start): array
    {
        $filed = $start->copy();
        $decided = $filed->copy()->addDays($this->faker->numberBetween(120, 900));
        $hasDecision = $decided->lt($this->today);

        if ($type === CaseType::Administrative) {
            $firstDecision = $filed->copy()->subDays($this->faker->numberBetween(60, 300));
            AdministrativeCaseDetail::query()->create([
                'case_id' => $case->id,
                'first_instance_decision' => $this->faker->randomElement(['Refuzim i lejes së ndërtimit (demo)', 'Refuzim i pensionit (demo)', 'Vendim për regjistrim të pronës (demo)', 'Ndërprerje e marrëdhënies së punës (demo)']),
                'first_instance_decision_date' => $firstDecision->toDateString(),
                'decision_reasoned' => $this->faker->boolean(60),
                'decision_received_date' => $firstDecision->copy()->addDays($this->faker->numberBetween(3, 40))->toDateString(),
                'legal_remedy_type' => $this->faker->randomElement([LegalRemedyType::AgainstDecision, LegalRemedyType::AgainstDecision, LegalRemedyType::AgainstSilence]),
                'legal_remedy_date' => $firstDecision->copy()->addDays($this->faker->numberBetween(20, 60))->toDateString(),
                'second_instance_outcome' => $this->faker->randomElement(SecondInstanceOutcome::cases()),
                'second_instance_decision_date' => $filed->copy()->subDays($this->faker->numberBetween(5, 40))->toDateString(),
                'conflict_filed' => true,
                'conflict_filed_date' => $filed->toDateString(),
                'plaintiff_type' => $this->faker->randomElement(PlaintiffType::cases()),
                'respondent_body' => $this->faker->randomElement(['Ministria e Brendshme', 'Komuna', 'Agjencia Kadastrale e Kosovës', 'Ministria e Arsimit']),
                'representation' => $this->faker->randomElement(Representation::cases()),
                'first_review_date' => $filed->copy()->addDays($this->faker->numberBetween(60, 300))->min($this->today)->toDateString(),
                'last_review_date' => $hasDecision ? $decided->toDateString() : null,
            ]);
        } else {
            CivilCaseDetail::query()->create([
                'case_id' => $case->id,
                'claim_type' => $this->faker->randomElement(ClaimType::cases()),
                'claim_value_eur' => $this->faker->numberBetween(500, 250000),
                'filed_date' => $filed->toDateString(),
                'plaintiff_type' => $this->faker->randomElement([PlaintiffType::NaturalPerson, PlaintiffType::LegalPerson]),
                'respondent_type' => $this->faker->randomElement([PlaintiffType::NaturalPerson, PlaintiffType::LegalPerson]),
                'representation' => $this->faker->randomElement([Representation::Lawyer, Representation::Lawyer, Representation::Self]),
                'legal_aid' => $this->faker->boolean(15),
                'mediation_attempted' => $this->faker->boolean(20),
                'outcome' => $hasDecision ? $this->faker->randomElement([CivilOutcome::Granted, CivilOutcome::Rejected, CivilOutcome::PartiallyGranted, CivilOutcome::Settled, CivilOutcome::Withdrawn]) : CivilOutcome::Pending,
                'decision_date' => $hasDecision ? $decided->toDateString() : null,
            ]);
        }

        return ['filed' => $filed, 'decided' => $hasDecision ? $decided : $this->today->copy()];
    }

    /**
     * @param  Collection<int, CaseParty>  $accused
     * @param  array<string, Carbon>  $timeline
     */
    private function seedCharges(CourtCase $case, Collection $accused, CaseType $type, array $timeline): void
    {
        $pool = $type === CaseType::CriminalCorruption ? $this->offences['corruption'] : $this->offences['other'];

        if ($pool->isEmpty()) {
            $pool = $this->offences['corruption']->merge($this->offences['other']);
        }

        foreach ($accused as $party) {
            $offences = $pool->random(min($pool->count(), $this->faker->numberBetween(1, 2)));

            foreach ($offences as $offence) {
                Charge::query()->create(['case_id' => $case->id, 'case_party_id' => $party->id, 'criminal_offence_id' => $offence->id, 'stage' => ChargeStage::CriminalReport]);

                if (isset($timeline[StageType::IndictmentFiled->value])) {
                    Charge::query()->create(['case_id' => $case->id, 'case_party_id' => $party->id, 'criminal_offence_id' => $offence->id, 'stage' => ChargeStage::Indictment]);
                }

                if (isset($timeline[StageType::JudgmentAnnounced->value])) {
                    Charge::query()->create(['case_id' => $case->id, 'case_party_id' => $party->id, 'criminal_offence_id' => $offence->id, 'stage' => ChargeStage::Judgment]);
                }
            }
        }
    }

    /**
     * @param  Collection<int, CaseParty>  $accused
     * @param  array<string, Carbon>  $timeline
     */
    private function seedMeasures(CourtCase $case, Collection $accused, CaseType $type, array $timeline): void
    {
        $start = $timeline[StageType::CriminalReportFiled->value] ?? $this->today;
        $person = $accused->first(fn (CaseParty $party): bool => $party->role === PartyRole::Accused);

        if ($person !== null && $this->faker->boolean(40)) {
            $requested = $start->copy()->addDays($this->faker->numberBetween(1, 40))->min($this->today);
            Measure::query()->create([
                'case_id' => $case->id,
                'case_party_id' => $person->id,
                'kind' => MeasureKind::SecurityMeasure,
                'security_measure_type_id' => SecurityMeasureType::query()->inRandomOrder()->value('id'),
                'requested' => true,
                'requested_on' => $requested->toDateString(),
                'granted' => $this->faker->boolean(65),
                'decided_on' => $requested->copy()->addDays($this->faker->numberBetween(0, 3))->min($this->today)->toDateString(),
            ]);
        }

        if (in_array($type, [CaseType::CriminalCorruption, CaseType::CriminalSpecial, CaseType::CriminalSerious], true)) {
            foreach ([[MeasureKind::Seizure, 35, 60], [MeasureKind::Confiscation, 22, 45]] as [$kind, $chance, $grantChance]) {
                if (! $this->faker->boolean($chance)) {
                    continue;
                }

                $requested = $start->copy()->addDays($this->faker->numberBetween(10, 300))->min($this->today);
                $decided = $this->faker->boolean(80);

                Measure::query()->create([
                    'case_id' => $case->id,
                    'case_party_id' => $accused->random()->id,
                    'kind' => $kind,
                    'requested' => true,
                    'requested_on' => $requested->toDateString(),
                    'granted' => $decided ? $this->faker->boolean($grantChance) : null,
                    'decided_on' => $decided ? $requested->copy()->addDays($this->faker->numberBetween(5, 120))->min($this->today)->toDateString() : null,
                    'amount_eur' => $this->faker->numberBetween(2000, 900000),
                    'description' => $this->faker->randomElement(['Vetura (demo)', 'Llogari bankare (demo)', 'Pronë e paluajtshme (demo)', 'Para të gatshme (demo)']),
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, CaseParty>  $accused
     * @param  array<string, Carbon>  $timeline
     */
    private function seedSentences(CourtCase $case, Collection $accused, array $timeline): void
    {
        $judgment = CaseStage::query()->where('case_id', $case->id)->where('stage', StageType::JudgmentAnnounced->value)->first();

        if ($judgment === null || ! in_array($judgment->outcome, [StageOutcome::Convicting, StageOutcome::Mixed], true)) {
            return;
        }

        foreach ($accused as $party) {
            $requestedPrison = $this->faker->randomElement([0, 6, 12, 24, 36, 48, 60, 84]);
            $imposedPrison = (int) round($requestedPrison * $this->faker->randomFloat(2, 0.3, 1.0));
            $suspended = $imposedPrison === 0 ? $this->faker->randomElement([6, 12, 18, 24]) : 0;

            Sentence::query()->create([
                'case_id' => $case->id,
                'case_party_id' => $party->id,
                'kind' => SentenceKind::Requested,
                'instance' => Instance::FirstInstance,
                'prison_months' => $requestedPrison,
                'fine_eur' => $this->faker->randomElement([0, 1000, 2500, 5000, 10000]),
            ]);

            Sentence::query()->create([
                'case_id' => $case->id,
                'case_party_id' => $party->id,
                'kind' => SentenceKind::Imposed,
                'instance' => Instance::FirstInstance,
                'prison_months' => $imposedPrison,
                'suspended_months' => $suspended,
                'fine_eur' => $this->faker->randomElement([0, 500, 1000, 2000, 5000]),
                'supplementary_punishment' => $this->faker->boolean(25) ? 'Ndalim i ushtrimit të funksionit publik (demo)' : null,
            ]);
        }
    }

    /**
     * @param  array<string, Carbon>  $timeline
     * @param  Collection<int, CaseParty>  $accused
     */
    private function seedHearings(CourtCase $case, ?Monitor $monitor, array $timeline, WorkflowStatus $caseStatus, Collection $accused): void
    {
        $from = $timeline[StageType::IndictmentFiled->value] ?? $timeline['filed'] ?? null;

        if ($from === null) {
            return;
        }

        $until = $timeline[StageType::JudgmentAnnounced->value] ?? $timeline['decided'] ?? $this->today;
        $until = $until->copy()->min($this->today->copy()->subDay());

        if ($until->lte($from)) {
            return;
        }

        // Some courts postpone more often — makes the analytics meaningful.
        $postponeRate = 22 + ($case->court_id % 4) * 8;
        $lateRate = 18 + ($case->court_id % 3) * 9;
        $count = $this->faker->numberBetween(2, 7);

        for ($n = 0; $n < $count; $n++) {
            $date = Carbon::instance($this->faker->dateTimeBetween($from, $until))->startOfDay();
            $late = $this->faker->boolean($lateRate);
            $postponed = $this->faker->boolean($postponeRate);
            $interpretation = $this->faker->boolean(12);
            $phone = $this->faker->boolean(15);

            $hearing = new Hearing;
            $hearing->fill([
                'case_id' => $case->id,
                'monitor_id' => $monitor?->id,
                'hearing_date' => $date->toDateString(),
                'hearing_type' => $case->type->isCriminal()
                    ? $this->faker->randomElement([HearingType::Initial, HearingType::Second, HearingType::MainTrial, HearingType::MainTrial, HearingType::MainTrial, HearingType::ClosingArguments])
                    : $this->faker->randomElement([HearingType::Preparatory, HearingType::MainTrial, HearingType::MainTrial]),
                'court_id' => $case->court_id,
                'judge_id' => $case->judge_id,
                'prosecutor_id' => $case->prosecutor_id,
                'venue' => $this->faker->boolean(85) ? Venue::Courtroom : Venue::Office,
                'is_public' => $this->faker->boolean(88),
                'started_late' => $late,
                'delay_minutes' => $late ? $this->faker->numberBetween(16, 95) : null,
                'delay_cause' => $late ? $this->faker->randomElement([DelayCause::Panel, DelayCause::Panel, DelayCause::Prosecutor, DelayCause::Defence, DelayCause::Other]) : null,
                'was_postponed' => $postponed,
                'postponement_reasons' => $postponed ? $this->faker->randomElements([
                    PostponementReason::Accused->value, PostponementReason::DefenceLawyer->value, PostponementReason::DefenceLawyer->value,
                    PostponementReason::Prosecutor->value, PostponementReason::Judge->value, PostponementReason::InjuredParty->value,
                    PostponementReason::Expert->value, PostponementReason::Other->value,
                ], $this->faker->numberBetween(1, 2)) : null,
                'phone_used' => $phone,
                'phone_users' => $phone ? $this->faker->randomElements(PhoneUser::values(), $this->faker->numberBetween(1, 2)) : null,
                'security_adequate' => $this->faker->boolean(90),
                'recording' => $this->faker->randomElement([Recording::None, Recording::None, Recording::Audio, Recording::Audio, Recording::AudioVideo]),
                'interpretation_needed' => $interpretation,
                'interpretation_provided' => $interpretation ? $this->faker->boolean(80) : null,
                'participants' => array_values(array_unique([
                    Participant::Judge->value, Participant::Prosecutor->value,
                    ...$this->faker->randomElements([Participant::DefenceLawyer->value, Participant::Accused->value, Participant::Witness->value, Participant::Public->value, Participant::InjuredParty->value], $this->faker->numberBetween(1, 4)),
                ])),
            ]);

            $hearing->status = match (true) {
                $caseStatus === WorkflowStatus::Published => WorkflowStatus::Published,
                $caseStatus->isVerified() => $this->faker->boolean(92) ? WorkflowStatus::Approved : WorkflowStatus::Submitted,
                default => $caseStatus,
            };
            $hearing->created_by = $monitor?->user_id;
            $hearing->submitted_at = $hearing->status !== WorkflowStatus::Draft ? $date->copy()->addDays(1) : null;
            $hearing->approved_at = $hearing->status->isVerified() ? $date->copy()->addDays(5) : null;
            $hearing->approved_by = $hearing->status->isVerified() ? $this->editor?->id : null;
            $hearing->created_at = $date->copy()->setTime(15, 0);
            $hearing->updated_at = $date->copy()->addDays(5)->min($this->today);
            $hearing->save();

            if ($accused->isNotEmpty()) {
                $hearing->presentParties()->sync($accused->random($this->faker->numberBetween(1, $accused->count()))->pluck('id'));
            }
        }
    }

    private function seedWorkflow(CourtCase $case, WorkflowStatus $status, ?Monitor $monitor): void
    {
        $submittedAt = $case->created_at?->copy()->addDays(3);

        if ($status === WorkflowStatus::Draft) {
            return;
        }

        WorkflowTransition::query()->create([
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->id,
            'from_status' => WorkflowStatus::Draft,
            'to_status' => WorkflowStatus::Submitted,
            'user_id' => $monitor?->user_id,
            'created_at' => $submittedAt,
        ]);
        $case->submitted_at = $submittedAt;

        if ($status === WorkflowStatus::Returned) {
            $case->return_comment = 'Ju lutem plotësoni datën e ngritjes së aktakuzës dhe kontrolloni palët. (demo)';
            $case->returned_at = $submittedAt?->copy()->addDays(2);
            WorkflowTransition::query()->create([
                'subject_type' => $case->getMorphClass(),
                'subject_id' => $case->id,
                'from_status' => WorkflowStatus::Submitted,
                'to_status' => WorkflowStatus::Returned,
                'user_id' => $this->editor?->id,
                'comment' => $case->return_comment,
                'created_at' => $case->returned_at,
            ]);
        }

        if ($status->isVerified()) {
            $case->approved_at = $submittedAt?->copy()->addDays(4);
            $case->approved_by = $this->editor?->id;
            WorkflowTransition::query()->create([
                'subject_type' => $case->getMorphClass(),
                'subject_id' => $case->id,
                'from_status' => WorkflowStatus::Submitted,
                'to_status' => WorkflowStatus::Approved,
                'user_id' => $this->editor?->id,
                'snapshot' => $case->workflowSnapshot(),
                'created_at' => $case->approved_at,
            ]);
        }

        if ($status === WorkflowStatus::Published) {
            $case->published_at = $case->approved_at?->copy()->addDays(10);
            WorkflowTransition::query()->create([
                'subject_type' => $case->getMorphClass(),
                'subject_id' => $case->id,
                'from_status' => WorkflowStatus::Approved,
                'to_status' => WorkflowStatus::Published,
                'user_id' => $this->editor?->id,
                'created_at' => $case->published_at,
            ]);
        }

        $case->saveQuietly();
    }
}
