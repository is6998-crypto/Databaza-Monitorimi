<?php

namespace Migration\Import;

use App\Enums\Appellant;
use App\Enums\CaseType;
use App\Enums\CivilOutcome;
use App\Enums\ClaimType;
use App\Enums\CounselType;
use App\Enums\DelayCause;
use App\Enums\Gender;
use App\Enums\HearingType;
use App\Enums\LegalRemedyType;
use App\Enums\MeasureKind;
use App\Enums\Participant;
use App\Enums\PartyRole;
use App\Enums\PhoneUser;
use App\Enums\PlaintiffType;
use App\Enums\PostponementReason;
use App\Enums\Recording;
use App\Enums\Representation;
use App\Enums\SecondInstanceOutcome;
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
use App\Models\Reference\DefendantPosition;
use App\Models\Reference\EconomicStatus;
use App\Models\Reference\Nationality;
use App\Models\Reference\ReferenceModel;
use App\Models\Reference\SecurityMeasureType;
use App\Models\Sentence;
use App\Services\StageChronology;
use App\Support\BlindIndex;
use BackedEnum;
use Illuminate\Support\Carbon;
use Migration\Support\Text;
use Migration\Support\ValueMapper;

/**
 * Writes one case group (the legacy rows of one case) into the new schema: the case
 * and its details, parties, charges, stages, measures, sentences and hearings.
 *
 * Case-level values are taken from the latest row that has them ("latest wins");
 * conflicting values are sent to review. A re-run only adds what is new: records that
 * were already imported (and may since have been corrected by staff) are never changed.
 */
final class CaseImporter
{
    private const COMPANY_MARKERS = ['shpk', 'sh p k', 'llc', 'l l c', 'sh a', 'ntp', 'n t p', 'kompania', 'ndermarrja', 'korporata', 'company', 'doo', 'd o o'];

    /** @var array<int, true> parties created in this run (their blanks may still be filled) */
    private array $createdParties = [];

    /** @var array<string, int> hearing date => hearing id, for the current case */
    private array $hearings = [];

    public function __construct(
        private readonly ReferenceResolver $refs,
        private readonly IdMap $ids,
        private readonly ValueMapper $values,
        private readonly WorkflowStatus $status,
        private readonly string $chargeStage = 'indictment',
    ) {}

    /**
     * @param  list<LegacyRow>  $rows  every row of the case, sorted by hearing date then id
     */
    public function import(ModuleConfig $module, array $rows, ModuleStats $stats): void
    {
        $mapped = $this->ids->many($module->table, array_map(fn (LegacyRow $row): string => $row->id(), $rows), 'case');
        $new = [];

        foreach ($rows as $row) {
            if (isset($mapped[$row->id()])) {
                $stats->rowsAlreadyImported++;
            } elseif ($row->isEmpty()) {
                $row->flag('*', ReviewLog::EMPTY_ROW, null, [], false);
                $stats->rowsEmpty++;
            } else {
                $new[] = $row;
            }
        }

        if ($new === []) {
            return;
        }

        $existing = $mapped === [] ? null : CourtCase::withTrashed()->find(reset($mapped));
        $case = $existing ?? $this->createCase($module, $new, $stats);

        $this->hearings = Hearing::withTrashed()->where('case_id', $case->id)->get(['id', 'hearing_date'])
            ->mapWithKeys(fn (Hearing $hearing): array => [$hearing->hearing_date->toDateString() => $hearing->id])
            ->all();

        foreach ($new as $row) {
            $this->importRow($module, $case, $row, $stats);
            $this->ids->put($module->table, $row->id(), 'case', $case->id);
            $stats->rowsImported++;
        }

        $this->stages($module, $case, $new, $stats);
        $this->details($module, $case, $new, $stats);
    }

    // ---------------------------------------------------------------- case

    /**
     * @param  list<LegacyRow>  $rows
     */
    private function createCase(ModuleConfig $module, array $rows, ModuleStats $stats): CourtCase
    {
        $court = $this->pick($rows, 'court', function (string $raw, LegacyRow $row): ?array {
            $match = $this->refs->court($raw);

            if ($match === null) {
                $row->flag('court', ReviewLog::UNKNOWN_REFERENCE, $raw);
            }

            return $match;
        });
        $courtId = $court[0] ?? null;
        $branchId = $court[1] ?? null;

        if ($courtId !== null) {
            $branchId = $this->pick($rows, 'court_branch', function (string $raw, LegacyRow $row) use ($courtId): ?int {
                $id = $this->refs->branch($courtId, $raw);

                if ($id === null) {
                    $row->flag('court_branch', ReviewLog::UNKNOWN_REFERENCE, $raw);
                }

                return $id;
            }) ?? $branchId;
        }

        $departmentId = $this->pick($rows, 'department', function (string $raw, LegacyRow $row): ?int {
            $id = $this->refs->department($raw);

            if ($id === null) {
                $row->flag('department', ReviewLog::UNKNOWN_REFERENCE, $raw);
            }

            return $id;
        }) ?? $this->refs->departmentByCode($module->defaults['department'] ?? null);

        $officeId = $this->pick($rows, 'prosecution_office', function (string $raw, LegacyRow $row): ?int {
            $id = $this->refs->prosecutionOffice($raw);

            if ($id === null) {
                $row->flag('prosecution_office', ReviewLog::UNKNOWN_REFERENCE, $raw);
            }

            return $id;
        });

        $monitorId = $this->pick($rows, 'monitor', function (string $raw, LegacyRow $row): ?int {
            $id = $this->refs->monitor($raw);

            if ($id === null) {
                $row->flag('monitor', ReviewLog::UNKNOWN_REFERENCE, $raw);
            }

            return $id;
        });

        $number = fn (string $field): ?string => $this->pick(
            $rows,
            $field,
            fn (string $raw, LegacyRow $row): ?string => $row->text($field, 64),
            fn (string $value): string => Text::caseNumberKey($value),
        );

        $case = new CourtCase([
            'type' => $module->caseType,
            'court_id' => $courtId,
            'court_branch_id' => $branchId,
            'department_id' => $departmentId,
            'judge_id' => $this->pick($rows, 'judge', fn (string $raw): ?int => $this->refs->judge($raw, $courtId)),
            'prosecutor_id' => $this->pick($rows, 'prosecutor', fn (string $raw): ?int => $this->refs->prosecutor($raw, $officeId)),
            'prosecution_office_id' => $officeId,
            'assigned_monitor_id' => $monitorId,
            'court_case_number' => $number('court_case_number'),
            'indictment_number' => $number('indictment_number'),
            'police_case_number' => $number('police_case_number'),
            'summary' => $this->pick($rows, 'summary', fn (string $raw, LegacyRow $row): ?string => $row->text('summary', 20000)),
            'legacy_module' => $module->key,
            'legacy_id' => $rows[0]->id(),
        ]);
        $case->forceFill($this->statusAttributes());

        $created = array_filter(array_map(fn (LegacyRow $row): ?string => $row->createdAt(), $rows));

        if ($created !== []) {
            $case->created_at = Carbon::parse(min($created));
        }

        $case->save();
        $stats->created('cases');

        return $case;
    }

    // ---------------------------------------------------------------- one row

    private function importRow(ModuleConfig $module, CourtCase $case, LegacyRow $row, ModuleStats $stats): void
    {
        $present = [];
        $main = null;

        if ($module->isCriminal()) {
            $main = $this->party($module, $case, $row, 'party_name', PartyRole::Accused, $stats, details: true);
            $present[] = $main;
            $present[] = $this->party($module, $case, $row, 'injured_party_name', PartyRole::InjuredParty, $stats);

            $this->charge($module, $case, $main, $row, $stats);
            $this->sentences($module, $case, $main, $row, $stats);
            $this->measures($case, $main, $row, $stats);
        } else {
            $present[] = $this->party($module, $case, $row, 'plaintiff_name', PartyRole::Plaintiff, $stats);
            $present[] = $this->party($module, $case, $row, 'respondent_name', PartyRole::Respondent, $stats);
        }

        $hearing = $this->hearing($module, $case, $row, array_values(array_filter($present)), $stats);

        if ($hearing !== null) {
            $this->ids->put($module->table, $row->id(), 'hearing', $hearing->id);
        }

        $unmapped = $row->unmappedColumns();

        if ($unmapped !== []) {
            $row->flag('*', ReviewLog::UNMAPPED_COLUMNS, implode(', ', array_keys($unmapped)), ['columns' => $unmapped, 'case_id' => $case->id], false);
        }
    }

    private function party(ModuleConfig $module, CourtCase $case, LegacyRow $row, string $nameField, PartyRole $role, ModuleStats $stats, bool $details = false): ?CaseParty
    {
        $name = $row->text($nameField, 255);
        $attributes = $details ? $this->partyAttributes($row) : [];

        if ($name === null && array_filter($attributes, fn (mixed $value): bool => $value !== null) === [] && ! ($details && ($row->has('article') || $row->has('offence')))) {
            return null;
        }

        if ($role === PartyRole::Accused && (($attributes['gender'] ?? null) === Gender::LegalPerson || $this->looksLikeCompany($name))) {
            $role = PartyRole::LegalEntity;
            $attributes['gender'] = Gender::LegalPerson;
        }

        $key = 'party:'.$case->id.':'.$role->value.':'.($name === null ? 'unnamed' : BlindIndex::hash(Text::personKey($name)));
        $existingId = $this->ids->get($module->table, $key, 'party');
        $party = $existingId === null ? null : CaseParty::withTrashed()->find($existingId);

        if ($party !== null) {
            if (isset($this->createdParties[$party->id])) {
                foreach ($attributes as $attribute => $value) {
                    if ($value !== null && $party->getAttribute($attribute) === null) {
                        $party->setAttribute($attribute, $value);
                    }
                }

                $party->save();
            }

            return $party;
        }

        $party = new CaseParty([...array_filter($attributes, fn (mixed $value): bool => $value !== null), 'role' => $role, 'name' => $name]);
        $party->case_id = $case->id;
        $party->legacy_id = $row->id();
        $party->save();

        $this->ids->put($module->table, $key, 'party', $party->id);
        $this->createdParties[$party->id] = true;
        $stats->created('parties');

        return $party;
    }

    /**
     * @return array<string, mixed>
     */
    private function partyAttributes(LegacyRow $row): array
    {
        return [
            'gender' => $row->enum('party_gender', Gender::class),
            'nationality_id' => $this->listValue($row, 'party_nationality', Nationality::class),
            'defendant_position_id' => $this->listValue($row, 'party_position', DefendantPosition::class),
            'economic_status_id' => $this->listValue($row, 'party_economic_status', EconomicStatus::class),
            'counsel_type' => $row->enum('party_counsel', CounselType::class),
            'counsel_at_police' => $row->bool('party_counsel_at_police'),
        ];
    }

    /**
     * @param  class-string<ReferenceModel>  $model
     */
    private function listValue(LegacyRow $row, string $field, string $model): ?int
    {
        $raw = $row->raw($field);
        $match = $this->refs->lookup($model, $raw);

        if ($match === null) {
            return null;
        }

        if ($match[1]) {
            $row->flag($field, ReviewLog::NEW_REFERENCE, $raw);
        }

        return $match[0];
    }

    private function charge(ModuleConfig $module, CourtCase $case, ?CaseParty $party, LegacyRow $row, ModuleStats $stats): void
    {
        $articleRaw = $row->raw('article');
        $titleRaw = $row->raw('offence');

        if ($articleRaw === null && $titleRaw === null) {
            return;
        }

        $date = $this->values->date($row->raw('stage.criminal_report_filed.date'))
            ?? $this->values->date($row->raw('stage.indictment_filed.date'))
            ?? $this->values->date($row->raw('hearing_date'));

        [$offenceId, $created, $description] = $this->refs->offence($row->raw('criminal_code'), $articleRaw, $titleRaw, $date);

        if ($offenceId === null) {
            $row->flag('article', ReviewLog::UNMAPPED_VALUE, $description);

            return;
        }

        if ($created) {
            $row->flag('article', ReviewLog::NEW_REFERENCE, $description);
        }

        $party ??= $this->party($module, $case, $row, 'party_name', PartyRole::Accused, $stats, details: true);

        if ($party === null) {
            return;
        }

        $charge = Charge::query()->firstOrCreate(
            ['case_party_id' => $party->id, 'criminal_offence_id' => $offenceId, 'stage' => $this->chargeStage],
            ['case_id' => $case->id],
        );

        if ($charge->wasRecentlyCreated) {
            $stats->created('charges');
        }
    }

    private function sentences(ModuleConfig $module, CourtCase $case, ?CaseParty $party, LegacyRow $row, ModuleStats $stats): void
    {
        $years = $row->int('sentence_prison_years', 0, 100);
        $months = $row->int('sentence_prison_months', 0, 1200);
        $imposed = [
            'prison_months' => $years === null && $months === null ? null : ($years ?? 0) * 12 + ($months ?? 0),
            'suspended_months' => $row->int('sentence_suspended_months', 0, 1200),
            'fine_eur' => $row->amount('sentence_fine'),
        ];
        $requested = ['prison_months' => $row->int('requested_prison_months', 0, 1200)];

        foreach (['imposed' => $imposed, 'requested' => $requested] as $kind => $values) {
            if (array_filter($values, fn (mixed $value): bool => $value !== null) === []) {
                continue;
            }

            $party ??= $this->party($module, $case, $row, 'party_name', PartyRole::Accused, $stats, details: true);

            if ($party === null) {
                return;
            }

            $sentence = Sentence::query()->firstOrCreate(
                ['case_party_id' => $party->id, 'kind' => $kind, 'instance' => 'first_instance'],
                ['case_id' => $case->id, ...$values],
            );

            if ($sentence->wasRecentlyCreated) {
                $stats->created('sentences');
            }
        }
    }

    private function measures(CourtCase $case, ?CaseParty $party, LegacyRow $row, ModuleStats $stats): void
    {
        foreach (['seizure' => MeasureKind::Seizure, 'confiscation' => MeasureKind::Confiscation] as $prefix => $kind) {
            $requested = $row->bool($prefix.'_requested');
            $granted = $row->bool($prefix.'_granted');

            if ($requested !== true && $granted !== true) {
                continue;
            }

            $measure = Measure::query()->firstOrCreate(
                ['case_id' => $case->id, 'kind' => $kind->value, 'case_party_id' => null],
                ['requested' => true, 'granted' => $granted],
            );

            if ($measure->wasRecentlyCreated) {
                $stats->created('measures');
            }
        }

        $type = $this->listValue($row, 'security_measure', SecurityMeasureType::class);

        if ($type !== null) {
            $measure = Measure::query()->firstOrCreate(
                ['case_id' => $case->id, 'kind' => MeasureKind::SecurityMeasure->value, 'case_party_id' => $party?->id, 'security_measure_type_id' => $type],
                ['requested' => true, 'granted' => true, 'requested_on' => $row->date('security_measure_date')],
            );

            if ($measure->wasRecentlyCreated) {
                $stats->created('measures');
            }
        }
    }

    /**
     * @param  list<CaseParty>  $present
     */
    private function hearing(ModuleConfig $module, CourtCase $case, LegacyRow $row, array $present, ModuleStats $stats): ?Hearing
    {
        if (! $row->has('hearing_date')) {
            return null;
        }

        $date = $row->date('hearing_date');

        if ($date === null) {
            // Keep every answer of the hearing for manual entry.
            $answers = [];

            foreach (['hearing_date', 'hearing_type', 'venue', 'is_public', 'started_late', 'delay_minutes', 'delay_cause', 'was_postponed', 'postponement_reasons', 'recording', 'interpretation_needed', 'interpretation_provided', 'phone_used', 'phone_users', 'security_adequate', 'participants', 'notes', 'monitor'] as $field) {
                $answers[$field] = $row->raw($field);
            }

            $row->flag('hearing_date', ReviewLog::INVALID_DATE, $row->raw('hearing_date'), ['hearing' => array_filter($answers), 'case_id' => $case->id], false);

            return null;
        }

        if (isset($this->hearings[$date])) {
            $hearing = Hearing::withTrashed()->find($this->hearings[$date]);

            if ($hearing !== null && $present !== []) {
                $hearing->presentParties()->syncWithoutDetaching(array_map(fn (CaseParty $party): int => $party->id, $present));
            }

            return $hearing;
        }

        $delay = $row->int('delay_minutes', 0, 720);
        $startedLate = $row->bool('started_late') ?? ($delay === null ? null : $delay > 0);
        $reasons = $row->enums('postponement_reasons', PostponementReason::class);
        $postponed = $row->bool('was_postponed') ?? ($reasons === [] ? null : true);
        $phoneUsers = $row->enums('phone_users', PhoneUser::class);
        $phoneUsed = $row->bool('phone_used') ?? ($phoneUsers === [] ? null : true);
        $interpretationProvided = $row->bool('interpretation_provided');
        $interpretationNeeded = $row->bool('interpretation_needed') ?? ($interpretationProvided === null ? null : true);

        if ($startedLate === false && (int) $delay > 0) {
            $row->flag('delay_minutes', ReviewLog::INCONSISTENT, 'started_late = no, delay_minutes = '.$delay, [], false);
        }

        if ($postponed === false && $reasons !== []) {
            $row->flag('postponement_reasons', ReviewLog::INCONSISTENT, 'was_postponed = no, reasons = '.implode(', ', $reasons), [], false);
        }

        $hearing = new Hearing([
            'case_id' => $case->id,
            'monitor_id' => $this->refs->monitor($row->raw('monitor')),
            'hearing_date' => $date,
            'hearing_type' => $row->enum('hearing_type', HearingType::class),
            'court_id' => $case->court_id,
            'judge_id' => $this->refs->judge($row->raw('judge'), $case->court_id) ?? $case->judge_id,
            'prosecutor_id' => $this->refs->prosecutor($row->raw('prosecutor'), $case->prosecution_office_id) ?? $case->prosecutor_id,
            'venue' => $row->enum('venue', Venue::class),
            'is_public' => $row->bool('is_public'),
            'started_late' => $startedLate ?? false,
            'delay_minutes' => $delay,
            'delay_cause' => $row->enum('delay_cause', DelayCause::class),
            'was_postponed' => $postponed ?? false,
            'postponement_reasons' => $reasons === [] ? null : $reasons,
            'phone_used' => $phoneUsed ?? false,
            'phone_users' => $phoneUsers === [] ? null : $phoneUsers,
            'security_adequate' => $row->bool('security_adequate'),
            'security_comment' => $row->text('security_comment', 5000),
            'recording' => $row->enum('recording', Recording::class),
            'interpretation_needed' => $interpretationNeeded ?? false,
            'interpretation_provided' => $interpretationProvided,
            'participants' => ($participants = $row->enums('participants', Participant::class)) === [] ? null : $participants,
            'notes' => $row->text('notes', 20000),
            'legacy_module' => $module->key,
            'legacy_id' => $row->id(),
        ]);
        $hearing->forceFill($this->statusAttributes());

        if (($created = $row->createdAt()) !== null) {
            $hearing->created_at = Carbon::parse($created);
        }

        $hearing->save();

        if ($present !== []) {
            $hearing->presentParties()->syncWithoutDetaching(array_map(fn (CaseParty $party): int => $party->id, $present));
        }

        $this->hearings[$date] = $hearing->id;
        $stats->created('hearings');

        return $hearing;
    }

    // ---------------------------------------------------------------- case-level details

    /**
     * Stages from the rows; on a re-run only stages the case does not have yet are added.
     *
     * @param  list<LegacyRow>  $rows
     */
    private function stages(ModuleConfig $module, CourtCase $case, array $rows, ModuleStats $stats): void
    {
        $existing = $case->stages()->get()->mapWithKeys(fn (CaseStage $stage): array => [$stage->stage->value => $stage->date?->toDateString()])->all();
        $dates = $existing;

        foreach (array_keys($module->stages) as $value) {
            $stage = StageType::tryFrom($value);

            if ($stage === null || array_key_exists($value, $existing)) {
                continue;
            }

            $date = $this->pick($rows, "stage.{$value}.date", fn (string $raw, LegacyRow $row): ?string => $row->date("stage.{$value}.date"));
            $outcome = $this->pick(
                $rows,
                "stage.{$value}.outcome",
                function (string $raw, LegacyRow $row) use ($stage, $value): ?StageOutcome {
                    $outcome = $row->enum("stage.{$value}.outcome", StageOutcome::class, 'stage_outcome.'.$value);

                    if ($outcome !== null && ! in_array($outcome, $stage->outcomes(), true)) {
                        $row->flag("stage.{$value}.outcome", ReviewLog::UNMAPPED_VALUE, $raw);

                        return null;
                    }

                    return $outcome;
                },
                fn (StageOutcome $outcome): string => $outcome->value,
            );
            $appellant = $this->pick(
                $rows,
                "stage.{$value}.appellant",
                fn (string $raw, LegacyRow $row): ?Appellant => $row->enum("stage.{$value}.appellant", Appellant::class),
                fn (Appellant $appellant): string => $appellant->value,
            );

            if ($date === null && $outcome === null && $appellant === null) {
                continue;
            }

            CaseStage::query()->create([
                'case_id' => $case->id,
                'stage' => $stage,
                'date' => $date,
                'outcome' => $outcome,
                'appellant' => $appellant,
                'is_direct_indictment' => false,
            ]);

            $dates[$value] = $date;
            $stats->created('stages');
        }

        $problems = StageChronology::checkSet($dates);

        if ($problems !== []) {
            $rows[0]->flag('stages', ReviewLog::CHRONOLOGY, implode(' ', $problems), ['case_id' => $case->id], false);
        }
    }

    /**
     * Civil / economic and administrative details, created once.
     *
     * @param  list<LegacyRow>  $rows
     */
    private function details(ModuleConfig $module, CourtCase $case, array $rows, ModuleStats $stats): void
    {
        $enum = fn (string $field, string $class): ?BackedEnum => $this->pickEnum($rows, $field, $class);
        $date = fn (string $field): ?string => $this->pick($rows, $field, fn (string $raw, LegacyRow $row): ?string => $row->date($field));
        $bool = fn (string $field): ?bool => $this->pick($rows, $field, fn (string $raw, LegacyRow $row): ?bool => $row->bool($field), fn (bool $value): string => $value ? '1' : '0');

        if (in_array($module->caseType, [CaseType::Civil, CaseType::Economic], true)) {
            if ($case->civilDetails()->exists()) {
                return;
            }

            $values = [
                'claim_type' => $enum('claim_type', ClaimType::class),
                'claim_value_eur' => $this->pick($rows, 'claim_value', fn (string $raw, LegacyRow $row): ?float => $row->amount('claim_value'), fn (float $value): string => (string) $value),
                'filed_date' => $date('filed_date'),
                'plaintiff_type' => $enum('plaintiff_type', PlaintiffType::class),
                'respondent_type' => $enum('respondent_type', PlaintiffType::class),
                'representation' => $enum('representation', Representation::class),
                'legal_aid' => $bool('legal_aid'),
                'mediation_attempted' => $bool('mediation_attempted'),
                'outcome' => $enum('civil_outcome', CivilOutcome::class),
                'decision_date' => $date('decision_date'),
            ];

            if (array_filter($values, fn (mixed $value): bool => $value !== null) !== []) {
                CivilCaseDetail::query()->create(['case_id' => $case->id, ...$values]);
                $stats->created('details');
            }

            return;
        }

        if ($module->caseType !== CaseType::Administrative || $case->administrativeDetails()->exists()) {
            return;
        }

        $values = [
            'first_instance_decision' => $this->pick($rows, 'first_instance_decision', fn (string $raw, LegacyRow $row): ?string => $row->text('first_instance_decision', 5000)),
            'first_instance_decision_date' => $date('first_instance_decision_date'),
            'decision_reasoned' => $bool('decision_reasoned'),
            'decision_received_date' => $date('decision_received_date'),
            'legal_remedy_type' => $enum('legal_remedy_type', LegalRemedyType::class),
            'legal_remedy_date' => $date('legal_remedy_date'),
            'second_instance_outcome' => $enum('second_instance_outcome', SecondInstanceOutcome::class),
            'second_instance_decision_date' => $date('second_instance_decision_date'),
            'conflict_filed' => $bool('conflict_filed'),
            'conflict_filed_date' => $date('conflict_filed_date'),
            'plaintiff_type' => $enum('plaintiff_type', PlaintiffType::class),
            'respondent_body' => $this->pick($rows, 'respondent_body', fn (string $raw, LegacyRow $row): ?string => $row->text('respondent_body', 255)),
            'representation' => $enum('representation', Representation::class),
            'first_review_date' => $date('first_review_date'),
            'last_review_date' => $date('last_review_date'),
        ];

        if (array_filter($values, fn (mixed $value): bool => $value !== null) !== []) {
            AdministrativeCaseDetail::query()->create(['case_id' => $case->id, ...$values, 'conflict_filed' => $values['conflict_filed'] ?? false]);
            $stats->created('details');
        }
    }

    // ---------------------------------------------------------------- helpers

    /**
     * The latest value of a case-level field across the rows of a case. Each distinct raw
     * value is resolved once; when the rows disagree, the disagreement goes to review.
     *
     * @template T
     *
     * @param  list<LegacyRow>  $rows
     * @param  callable(string, LegacyRow): (T|null)  $resolve
     * @param  (callable(T): string)|null  $compareKey
     * @return T|null
     */
    private function pick(array $rows, string $field, callable $resolve, ?callable $compareKey = null): mixed
    {
        $resolved = [];
        $distinct = [];
        $latest = null;

        foreach ($rows as $row) {
            $raw = $row->raw($field);

            if ($raw === null) {
                continue;
            }

            $rawKey = Text::key($raw).'|'.$raw;

            if (! array_key_exists($rawKey, $resolved)) {
                $resolved[$rawKey] = $resolve($raw, $row);
            }

            $value = $resolved[$rawKey];

            if ($value === null) {
                continue;
            }

            $distinct[$compareKey === null ? $this->compare($value) : $compareKey($value)] = $raw;
            $latest = $value;
        }

        if (count($distinct) > 1) {
            $rows[0]->flag($field, ReviewLog::CONFLICT, implode(' | ', array_values($distinct)), ['values' => array_values($distinct)]);
        }

        return $latest;
    }

    /**
     * @param  list<LegacyRow>  $rows
     * @param  class-string<BackedEnum>  $enum
     */
    private function pickEnum(array $rows, string $field, string $enum): ?BackedEnum
    {
        return $this->pick(
            $rows,
            $field,
            fn (string $raw, LegacyRow $row): ?BackedEnum => $row->enum($field, $enum),
            fn (BackedEnum $value): string => (string) $value->value,
        );
    }

    private function compare(mixed $value): string
    {
        return match (true) {
            $value instanceof BackedEnum => (string) $value->value,
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function statusAttributes(): array
    {
        return match ($this->status) {
            WorkflowStatus::Published => ['status' => WorkflowStatus::Published, 'approved_at' => now(), 'published_at' => now()],
            WorkflowStatus::Approved => ['status' => WorkflowStatus::Approved, 'approved_at' => now()],
            WorkflowStatus::Submitted, WorkflowStatus::InReview => ['status' => $this->status, 'submitted_at' => now()],
            default => ['status' => $this->status],
        };
    }

    private function looksLikeCompany(?string $name): bool
    {
        if ($name === null) {
            return false;
        }

        $key = ' '.Text::key($name).' ';

        foreach (self::COMPANY_MARKERS as $marker) {
            if (str_contains($key, ' '.$marker.' ')) {
                return true;
            }
        }

        return false;
    }
}
