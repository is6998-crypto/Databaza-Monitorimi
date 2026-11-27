<?php

return [
    'actions' => [
        'export' => 'Izvoz',
        'export_help' => 'Izvozi sve zapise koji odgovaraju trenutnim filterima i pretrazi. Izvoz se beleži. Imena stranaka se nikada ne uključuju.',
        'export_selected' => 'Izvezi izabrano',
        'assign_monitor' => 'Dodeli monitora',
        'change_status' => 'Promeni status',
    ],
    'fields' => [
        'format' => 'Format',
    ],
    'done' => [
        'assigned' => 'Monitor je dodeljen za :count predmet(a).',
        'status' => 'Status je promenjen za :done zapis(a); :skipped preskočeno (nije dozvoljeno u trenutnom statusu).',
    ],
    'columns' => [
        'parties' => 'Stranke',
        'accused' => 'Optuženi',
        'latest_stage_date' => 'Datum poslednje faze',
        'hearing_ref' => 'Oznaka ročišta',
    ],
    'log' => [
        'model' => 'Izvoz',
        'plural' => 'Evidencija izvoza',
        'context' => 'Gde',
        'resource' => 'Podaci',
        'rows' => 'Redovi',
        'anonymous' => 'Anonimni posetilac',
    ],
    'contexts' => [
        'admin' => 'Panel za osoblje',
        'researcher' => 'Oblast za istraživače',
        'api' => 'API',
        'public' => 'Otvoreni podaci',
    ],
];
