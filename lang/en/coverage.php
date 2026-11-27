<?php

return [
    'navigation' => 'Coverage',
    'title' => 'Monitoring coverage',
    'subheading' => 'Where the monitoring has gaps: cases nobody followed, hearings without a report, missing answers and forgotten drafts.',
    'open_list' => 'Open the list',
    'clear' => 'Nothing to do here.',
    'show_all' => '{1} Show all|[2,*] Show all :count',
    'show_less' => 'Show less',
    'last_hearing' => 'Last monitored hearing: :date',
    'never_monitored' => 'No monitored hearing',
    'no_monitor' => 'No monitor',
    'untouched_since' => 'untouched since :date',
    'checks' => [
        'not_followed' => [
            'title' => 'Active cases not followed',
            'description' => 'Cases with a legal time limit still running or a high priority, with no monitored hearing in the last :days days.',
        ],
        'no_monitor' => [
            'title' => 'High priority, no monitor',
            'description' => 'High-priority cases with no monitor assigned.',
        ],
        'missed_hearings' => [
            'title' => 'Planned hearings not monitored',
            'description' => 'Hearings in the calendar in the last :missed_days days for which no monitored hearing was recorded.',
        ],
        'missing_reports' => [
            'title' => 'Hearings without a report',
            'description' => 'Hearings of the last :report_window days, held more than :report_days days ago, that still have no written report.',
        ],
        'missing_facts' => [
            'title' => 'Missing answers for the time limits',
            'description' => 'Criminal cases where it is not known whether the accused is detained or who tries the case: until answered, the limits are counted with the longest time.',
        ],
        'stale_drafts' => [
            'title' => 'Forgotten drafts',
            'description' => 'Cases and hearings in draft or returned that nobody has touched for :draft_days days.',
        ],
    ],
];
