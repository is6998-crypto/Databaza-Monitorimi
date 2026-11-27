<?php

return [
    'navigation' => 'Mbulimi',
    'title' => 'Mbulimi i monitorimit',
    'subheading' => 'Ku ka zbrazëtira në monitorim: lëndë që nuk janë ndjekur, seanca pa raport, përgjigje që mungojnë dhe drafte të harruara.',
    'open_list' => 'Hap listën',
    'clear' => 'Asgjë për t\'u bërë këtu.',
    'show_all' => '{1} Shfaq të gjitha|[2,*] Shfaq të gjitha :count',
    'show_less' => 'Shfaq më pak',
    'last_hearing' => 'Seanca e fundit e monitoruar: :date',
    'never_monitored' => 'Asnjë seancë e monitoruar',
    'no_monitor' => 'Pa monitorues',
    'untouched_since' => 'e pandryshuar që nga :date',
    'checks' => [
        'not_followed' => [
            'title' => 'Lëndë aktive të pandjekura',
            'description' => 'Lëndë me një afat ligjor që ende rrjedh ose me prioritet të lartë, pa asnjë seancë të monitoruar në :days ditët e fundit.',
        ],
        'no_monitor' => [
            'title' => 'Prioritet i lartë pa monitorues',
            'description' => 'Lëndë me prioritet të lartë që nuk kanë monitorues të caktuar.',
        ],
        'missed_hearings' => [
            'title' => 'Seanca të planifikuara të pandjekura',
            'description' => 'Seanca të kalendarit në :missed_days ditët e fundit për të cilat nuk u regjistrua seancë e monitoruar.',
        ],
        'missing_reports' => [
            'title' => 'Seanca pa raport',
            'description' => 'Seanca të :report_window ditëve të fundit, të mbajtura më shumë se :report_days ditë më parë, që ende nuk kanë raport të shkruar.',
        ],
        'missing_facts' => [
            'title' => 'Përgjigje që mungojnë për afatet',
            'description' => 'Lëndë penale ku nuk dihet nëse i akuzuari është në paraburgim ose kush e gjykon lëndën: deri sa të plotësohen, afatet llogariten me kohën më të gjatë.',
        ],
        'stale_drafts' => [
            'title' => 'Drafte të harruara',
            'description' => 'Lëndë dhe seanca në draft ose të kthyera që nuk janë prekur prej :draft_days ditësh.',
        ],
    ],
];
