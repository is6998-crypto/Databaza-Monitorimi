<?php

return [
    'navigation' => 'Pokrivenost',
    'title' => 'Pokrivenost monitoringa',
    'subheading' => 'Gde monitoring ima praznine: predmeti koji se ne prate, ročišta bez izveštaja, odgovori koji nedostaju i zaboravljeni nacrti.',
    'open_list' => 'Otvori listu',
    'clear' => 'Ovde nema ničeg za uraditi.',
    'show_all' => '{1} Prikaži sve|[2,*] Prikaži svih :count',
    'show_less' => 'Prikaži manje',
    'last_hearing' => 'Poslednje praćeno ročište: :date',
    'never_monitored' => 'Nijedno praćeno ročište',
    'no_monitor' => 'Bez monitora',
    'untouched_since' => 'nepromenjeno od :date',
    'checks' => [
        'not_followed' => [
            'title' => 'Aktivni predmeti koji se ne prate',
            'description' => 'Predmeti sa zakonskim rokom koji još teče ili visokog prioriteta, bez praćenog ročišta u poslednjih :days dana.',
        ],
        'no_monitor' => [
            'title' => 'Visok prioritet bez monitora',
            'description' => 'Predmeti visokog prioriteta kojima nije dodeljen monitor.',
        ],
        'missed_hearings' => [
            'title' => 'Planirana ročišta koja nisu praćena',
            'description' => 'Ročišta iz kalendara u poslednjih :missed_days dana za koja nije uneto praćeno ročište.',
        ],
        'missing_reports' => [
            'title' => 'Ročišta bez izveštaja',
            'description' => 'Ročišta iz poslednjih :report_window dana, održana pre više od :report_days dana, koja još nemaju pisani izveštaj.',
        ],
        'missing_facts' => [
            'title' => 'Odgovori koji nedostaju za rokove',
            'description' => 'Krivični predmeti u kojima se ne zna da li je optuženi u pritvoru ili ko sudi: dok se ne odgovori, rokovi se računaju sa najdužim vremenom.',
        ],
        'stale_drafts' => [
            'title' => 'Zaboravljeni nacrti',
            'description' => 'Predmeti i ročišta u nacrtu ili vraćeni koje niko nije dirao :draft_days dana.',
        ],
    ],
];
