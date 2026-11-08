<?php

// Albanian for strings Filament's own Albanian translation lacks (merged over the package's file).

return [
    'label' => 'Çaktivizo',
    'modal' => [
        'heading' => 'Çaktivizo aplikacionin e autentifikimit',
        'description' => 'Jeni të sigurt që doni të ndaloni përdorimin e aplikacionit të autentifikimit? Kjo heq një shtresë shtesë sigurie nga llogaria juaj.',
        'form' => [
            'password' => [
                'label' => 'Fjalëkalimi aktual',
                'validation_attribute' => 'fjalëkalimi aktual',
            ],
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
                    'rate_limited' => 'Shumë përpjekje. Provoni përsëri më vonë.',
                ],
            ],
            'recovery_code' => [
                'label' => 'Ose shkruani një kod rikuperimi',
                'validation_attribute' => 'kodi i rikuperimit',
                'messages' => [
                    'invalid' => 'Kodi i rikuperimit që shkruat është i pavlefshëm.',
                    'rate_limited' => 'Shumë përpjekje. Provoni përsëri më vonë.',
                ],
            ],
        ],
        'actions' => [
            'submit' => [
                'label' => 'Çaktivizo aplikacionin e autentifikimit',
            ],
        ],
    ],
    'notifications' => [
        'disabled' => [
            'title' => 'Aplikacioni i autentifikimit u çaktivizua',
        ],
    ],
];
