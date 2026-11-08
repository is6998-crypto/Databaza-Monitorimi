<?php

// Albanian for strings Filament's own Albanian translation lacks (merged over the package's file).

return [
    'label' => 'Krijo kode të reja rikuperimi',
    'modal' => [
        'heading' => 'Krijo kode të reja rikuperimi për aplikacionin e autentifikimit',
        'description' => 'Nëse i humbni kodet e rikuperimit, mund të krijoni të reja këtu. Kodet e vjetra bëhen të pavlefshme menjëherë.',
        'form' => [
            'code' => [
                'label' => 'Shkruani kodin me 6 shifra nga aplikacioni i autentifikimit',
                'validation_attribute' => 'kodi',
                'messages' => [
                    'invalid' => 'Kodi që shkruat është i pavlefshëm.',
                    'rate_limited' => 'Shumë përpjekje. Provoni përsëri më vonë.',
                ],
            ],
            'password' => [
                'label' => 'Shkruani fjalëkalimin aktual',
                'validation_attribute' => 'fjalëkalimi',
            ],
        ],
        'actions' => [
            'submit' => [
                'label' => 'Krijo kode të reja rikuperimi',
            ],
        ],
    ],
    'notifications' => [
        'regenerated' => [
            'title' => 'U krijuan kode të reja rikuperimi për aplikacionin e autentifikimit',
        ],
    ],
    'show_new_recovery_codes' => [
        'modal' => [
            'heading' => 'Kodet e reja të rikuperimit',
            'description' => 'Ruajini kodet e rikuperimit më poshtë në një vend të sigurt. Shfaqen vetëm një herë, por do t\'ju duhen nëse humbni qasjen në aplikacionin e autentifikimit:',
            'actions' => [
                'submit' => [
                    'label' => 'Mbyll',
                ],
            ],
        ],
    ],
];
