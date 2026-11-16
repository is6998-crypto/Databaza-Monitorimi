<?php

namespace Database\Seeders;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\Hearing;
use App\Models\HearingReport;
use App\Models\HearingReportComment;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;

/**
 * FAKE hearing reports for development and demos: the monitor's written account of a session, some sent to the
 * editor, some sent back with a comment, some approved. The text is assembled from stock sentences and names
 * nobody: people appear by their role only.
 */
class DemoReportsSeeder extends Seeder
{
    private const OPENINGS = [
        'Seanca filloi në kohën e caktuar, në sallën e gjykimeve.',
        'Seanca filloi me rreth njëzet minuta vonesë, pasi trupi gjykues ishte i angazhuar në një seancë tjetër.',
        'Seanca u hap nga kryetari i trupit gjykues, i cili konstatoi se janë plotësuar kushtet për mbajtjen e saj.',
        'Seanca u mbajt e hapur për publikun; në sallë ishin të pranishëm edhe përfaqësues të mediave.',
    ];

    private const BODIES = [
        'Prokurori lexoi aktakuzën dhe paraqiti provat mbi të cilat mbështetet ajo. I akuzuari deklaroi se e ka kuptuar aktakuzën dhe nuk e pranoi fajësinë.',
        'Mbrojtja kundërshtoi një pjesë të provave materiale dhe kërkoi që ato të shpallen të papranueshme. Prokurori kërkoi që kundërshtimi të refuzohet si i pabazuar.',
        'U dëgjua një dëshmitar i propozuar nga prokuroria. Ai iu përgjigj pyetjeve të prokurorit dhe të mbrojtjes për rreth dyzet minuta.',
        'Eksperti financiar paraqiti gjetjet e ekspertizës. Mbrojtja kërkoi sqarime shtesë për mënyrën e llogaritjes së dëmit.',
        'Palët paraqitën fjalët përfundimtare. Prokurori kërkoi shpalljen fajtor të të akuzuarit, kurse mbrojtja kërkoi lirimin nga akuza.',
        'Mbrojtësi i të akuzuarit njoftoi gjykatën se nuk i ka pranuar me kohë të gjitha shkresat e lëndës dhe kërkoi afat shtesë për përgatitje.',
    ];

    private const CLOSINGS = [
        'Seanca e radhës u caktua pas tri javësh.',
        'Seanca u shty për shkak të mungesës së njërit prej të akuzuarve, i cili sipas mbrojtësit nuk e ka pranuar ftesën.',
        'Gjykata njoftoi se vendimin do ta marrë jashtë seancës dhe do t\'ua dorëzojë palëve me shkrim.',
        'Kryetari i trupit gjykues e mbylli seancën dhe njoftoi se data e seancës së ardhshme do të caktohet me shkrim.',
    ];

    private const EDITOR_COMMENTS = [
        'Ju lutem shtoni sa zgjati seanca dhe a u incizua. (demo)',
        'Plotësoni arsyen e shtyrjes ashtu siç e tha gjykata, jo vetëm sipas mbrojtjes. (demo)',
        'Mungon data e seancës së radhës. (demo)',
    ];

    public function run(): void
    {
        if (HearingReport::query()->exists()) {
            return;
        }

        $faker = FakerFactory::create();
        $faker->seed(20261116);
        $editor = User::query()->whereHas('roles', fn ($query) => $query->where('name', Role::Editor->value))->first();

        activity()->disableLogging();

        $hearings = Hearing::query()
            ->whereNotNull('created_by')
            ->latest('hearing_date')
            ->limit(60)
            ->get();

        foreach ($hearings as $index => $hearing) {
            $status = match (true) {
                $index < 6 => ReportStatus::Draft,
                $index < 20 => ReportStatus::Submitted,
                $index < 26 => ReportStatus::Returned,
                default => ReportStatus::Approved,
            };

            $writtenAt = $hearing->hearing_date->copy()->addDay()->setTime(10, 30);

            $report = new HearingReport([
                'hearing_id' => $hearing->id,
                'title' => $faker->randomElement(['Dëgjohet dëshmitari i prokurorisë', 'Mbrojtja kundërshton provat', 'Shtyhet seanca', 'Palët japin fjalën përfundimtare', 'Lexohet aktakuza']),
                'body' => $this->body($faker),
                'created_by' => $hearing->created_by,
                'updated_by' => $hearing->created_by,
            ]);

            $report->forceFill([
                'status' => $status,
                'submitted_at' => $status === ReportStatus::Draft ? null : $writtenAt->copy()->addHours(2),
                'reviewed_at' => in_array($status, [ReportStatus::Returned, ReportStatus::Approved], true) ? $writtenAt->copy()->addDays(2) : null,
                'reviewed_by' => in_array($status, [ReportStatus::Returned, ReportStatus::Approved], true) ? $editor?->id : null,
                'created_at' => $writtenAt,
                'updated_at' => $writtenAt->copy()->addDays(2)->min(now()),
            ])->save();

            if ($editor !== null && ($status === ReportStatus::Returned || ($status === ReportStatus::Approved && $faker->boolean(35)))) {
                $at = $writtenAt->copy()->addDays(2)->min(now());

                $comment = new HearingReportComment(['hearing_report_id' => $report->id, 'user_id' => $editor->id, 'body' => $faker->randomElement(self::EDITOR_COMMENTS)]);
                $comment->forceFill(['created_at' => $at, 'updated_at' => $at])->save();

                if ($status === ReportStatus::Approved) {
                    $reply = new HearingReportComment(['hearing_report_id' => $report->id, 'user_id' => $hearing->created_by, 'body' => 'E plotësova, faleminderit. (demo)']);
                    $reply->forceFill(['created_at' => $at->copy()->addHours(3)->min(now()), 'updated_at' => $at->copy()->addHours(3)->min(now())])->save();
                }
            }
        }

        activity()->enableLogging();
    }

    private function body(Generator $faker): string
    {
        $paragraphs = [
            $faker->randomElement(self::OPENINGS),
            ...$faker->randomElements(self::BODIES, $faker->numberBetween(2, 3)),
            $faker->randomElement(self::CLOSINGS),
        ];

        return implode('', array_map(fn (string $paragraph): string => '<p>'.e($paragraph).'</p>', $paragraphs));
    }
}
