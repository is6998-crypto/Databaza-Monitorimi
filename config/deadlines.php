<?php

/*
|--------------------------------------------------------------------------
| Legal time limits of the criminal procedure (Criminal Procedure Code of Kosovo)
|--------------------------------------------------------------------------
| One entry per App\Enums\DeadlineRule. Each limit is [amount, unit] with the unit
| "days", "months" or "years"; which one applies to a case is decided in
| App\Services\Deadlines\DeadlineCalculator from the facts recorded on the case
| (detention, single judge or panel, complex case, extended investigation).
| Change a number here if the law changes, then run `php artisan deadlines:check`.
*/

return [

    'rules' => [

        // Art. 157: the investigation ends within two years; one more year for a complex case.
        'investigation' => [
            'article' => '157',
            'standard' => [2, 'years'],
            'extended' => [3, 'years'],
        ],

        // Art. 234: the indictment is filed within three months after the investigation ends.
        'indictment_filing' => [
            'article' => '234',
            'standard' => [3, 'months'],
        ],

        // Art. 236: the initial hearing is held within 30 days of the indictment; 15 days if the accused is detained.
        'initial_hearing' => [
            'article' => '236',
            'standard' => [30, 'days'],
            'detention' => [15, 'days'],
        ],

        // Art. 249: the main trial is scheduled within 30 days after the indictment is confirmed.
        'main_trial_scheduling' => [
            'article' => '249',
            'standard' => [30, 'days'],
        ],

        // Art. 310: the main trial ends within 90 days before a single trial judge, 120 days before a trial panel.
        'main_trial' => [
            'article' => '310',
            'single_judge' => [90, 'days'],
            'panel' => [120, 'days'],
        ],

        // Art. 365: the judgment is announced at once, at the latest three days after the main trial ends.
        'judgment_announcement' => [
            'article' => '365',
            'standard' => [3, 'days'],
        ],

        // Art. 368: the written judgment within 15 days (detention) or 30 days; up to 60 days in a complex case.
        'judgment_drafting' => [
            'article' => '368',
            'standard' => [30, 'days'],
            'detention' => [15, 'days'],
            'extended' => [60, 'days'],
        ],

        // Art. 405: with the accused in detention, the Court of Appeals decides within three months of receiving the file.
        'appeal_decision' => [
            'article' => '405',
            'detention' => [3, 'months'],
        ],
    ],

    // Phases that end within this many days are listed as "due soon".
    'due_soon_days' => 7,

    // A warning before a limit runs out, once per phase: when this share of the limit is used (80: day 48 of
    // 60), but never more than max_days_before days ahead. Limits shorter than min_limit_days get no warning.
    'warning' => [
        'used_percent' => 80,
        'max_days_before' => 30,
        'min_limit_days' => 7,
    ],

    // Up to this many exceeded limits are named in one notification; the rest are counted.
    'notification_lines' => 5,
];
