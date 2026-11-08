<?php

// Albanian for strings Filament's own Albanian translation lacks (merged over the package's file).

return [
    'management_schema' => [
        'actions' => [
            'label' => 'Aplikacioni i autentifikimit',
            'below_content' => 'Përdorni një aplikacion të sigurt që krijon një kod të përkohshëm për verifikimin gjatë hyrjes.',
            'messages' => [
                'enabled' => 'Aktiv',
                'disabled' => 'Joaktiv',
            ],
        ],
    ],
    'login_form' => [
        'label' => 'Përdor një kod nga aplikacioni i autentifikimit',
        'code' => [
            'label' => 'Shkruani kodin me 6 shifra nga aplikacioni i autentifikimit',
            'validation_attribute' => 'kodi',
            'actions' => [
                'use_recovery_code' => [
                    'label' => 'Përdor një kod rikuperimi',
                ],
            ],
            'messages' => [
                'invalid' => 'Kodi që shkruat është i pavlefshëm.',
            ],
        ],
        'recovery_code' => [
            'label' => 'Ose shkruani një kod rikuperimi',
            'validation_attribute' => 'kodi i rikuperimit',
            'messages' => [
                'invalid' => 'Kodi i rikuperimit që shkruat është i pavlefshëm.',
            ],
        ],
    ],
];
