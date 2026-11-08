<?php

// Albanian for strings Filament's own Albanian translation lacks (merged over the package's file).

return [
    'label' => 'Konfiguro',
    'modal' => [
        'heading' => 'Konfiguro aplikacionin e autentifikimit',
        'description' => 'Do t\'ju duhet një aplikacion si Google Authenticator (<x-filament::link href="https://itunes.apple.com/us/app/google-authenticator/id388497605" target="_blank">iOS</x-filament::link>, <x-filament::link href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Android</x-filament::link>) për ta përfunduar këtë proces.',
        'content' => [
            'qr_code' => [
                'instruction' => 'Skanoni këtë kod QR me aplikacionin e autentifikimit:',
                'alt' => 'Kod QR për t\'u skanuar me aplikacionin e autentifikimit',
            ],
            'text_code' => [
                'instruction' => 'Ose shkruani këtë kod me dorë:',
                'messages' => [
                    'copied' => 'U kopjua',
                ],
            ],
            'recovery_codes' => [
                'instruction' => 'Ruajini kodet e rikuperimit më poshtë në një vend të sigurt. Shfaqen vetëm një herë, por do t\'ju duhen nëse humbni qasjen në aplikacionin e autentifikimit:',
            ],
        ],
        'form' => [
            'password' => [
                'label' => 'Fjalëkalimi aktual',
                'validation_attribute' => 'fjalëkalimi aktual',
            ],
            'code' => [
                'label' => 'Shkruani kodin me 6 shifra nga aplikacioni i autentifikimit',
                'validation_attribute' => 'kodi',
                'below_content' => 'Kodin me 6 shifra nga aplikacioni do ta shkruani sa herë që hyni ose kryeni veprime të ndjeshme.',
                'messages' => [
                    'invalid' => 'Kodi që shkruat është i pavlefshëm.',
                    'rate_limited' => 'Shumë përpjekje. Provoni përsëri më vonë.',
                ],
            ],
        ],
        'actions' => [
            'submit' => [
                'label' => 'Aktivizo aplikacionin e autentifikimit',
            ],
        ],
    ],
    'notifications' => [
        'enabled' => [
            'title' => 'Aplikacioni i autentifikimit u aktivizua',
        ],
    ],
];
