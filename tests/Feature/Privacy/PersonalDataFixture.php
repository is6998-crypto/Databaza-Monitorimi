<?php

namespace Tests\Feature\Privacy;

use App\Enums\PartyRole;
use App\Enums\Role;
use App\Enums\WorkflowStatus;
use App\Models\CaseParty;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\Publication;
use App\Models\Reference\Court;
use App\Models\Reference\Judge;
use App\Models\Reference\Monitor;
use App\Models\Reference\Prosecutor;
use App\Models\User;

/**
 * Verified records full of distinctive personal names, used to prove that no public,
 * researcher or API response ever contains one of them.
 */
final class PersonalDataFixture
{
    /** @var list<string> */
    public const NAMES = [
        'Zenel Xhafolli', 'Xhafolli',
        'Mimoza Qorraj', 'Qorraj',
        'Kompania Sekretnica',
        'Gjyqtar Fshehtësie', 'Fshehtësie',
        'Prokuror Fshehtësie',
        'Monitor Fshehtësie',
    ];

    /** @var list<string> */
    public const CASE_NUMBERS = ['PKR.nr.31337/2024', 'PP/II.nr.31337/2023', '2023-QQ-31337'];

    /**
     * @return array{cases: list<CourtCase>, hearings: list<Hearing>, publication: Publication}
     */
    public static function create(): array
    {
        $court = Court::query()->where('level', 'basic')->orderBy('id')->firstOrFail();
        $judge = Judge::query()->create(['name' => 'Gjyqtar Fshehtësie', 'court_id' => $court->id]);
        $prosecutor = Prosecutor::query()->create(['name' => 'Prokuror Fshehtësie']);
        $monitorUser = User::factory()->withMfa()->create(['name' => 'Monitor Fshehtësie']);
        $monitorUser->assignRole(Role::Monitor->value);
        $monitor = Monitor::query()->create(['user_id' => $monitorUser->id, 'code' => 'MF-1']);

        $cases = [];
        $hearings = [];

        foreach ([WorkflowStatus::Approved, WorkflowStatus::Published] as $index => $status) {
            $case = CourtCase::factory()->create([
                'court_id' => $court->id,
                'judge_id' => $judge->id,
                'prosecutor_id' => $prosecutor->id,
                'assigned_monitor_id' => $monitor->id,
                'court_case_number' => self::CASE_NUMBERS[0].($index ? '-B' : ''),
                'indictment_number' => self::CASE_NUMBERS[1],
                'police_case_number' => self::CASE_NUMBERS[2],
                'summary' => 'Shënim i brendshëm për Zenel Xhafolli',
                'type' => 'criminal_corruption',
            ]);
            $case->forceFill(['status' => $status, 'approved_at' => now()])->save();

            CaseParty::factory()->create(['case_id' => $case->id, 'name' => 'Zenel Xhafolli']);
            CaseParty::factory()->create(['case_id' => $case->id, 'name' => 'Mimoza Qorraj', 'role' => PartyRole::InjuredParty]);
            CaseParty::factory()->role(PartyRole::LegalEntity)->create(['case_id' => $case->id, 'name' => 'Kompania Sekretnica SH.P.K.']);

            foreach (range(1, 6) as $n) {
                $hearing = Hearing::factory()->forCase($case)->make([
                    'monitor_id' => $monitor->id,
                    'notes' => 'Zenel Xhafolli mungoi.',
                    'security_comment' => 'Mimoza Qorraj u ankua.',
                    'hearing_date' => now()->subMonths($n)->toDateString(),
                ]);
                $hearing->status = WorkflowStatus::Approved;
                $hearing->save();
                $hearings[] = $hearing;
            }

            $cases[] = $case;
        }

        $publication = Publication::query()->create([
            'slug' => 'raport-test',
            'type' => 'report',
            'title_sq' => 'Raport testues',
            'summary_sq' => 'Përmbledhje',
            'published_on' => now()->subDay()->toDateString(),
            'is_published' => true,
        ]);

        return ['cases' => $cases, 'hearings' => $hearings, 'publication' => $publication];
    }

    public static function assertClean(string $body, string $where): void
    {
        foreach ([...self::NAMES, ...self::CASE_NUMBERS] as $needle) {
            expect(mb_stripos($body, $needle))->toBeFalse("“{$needle}” leaked in {$where}");
        }
    }
}
