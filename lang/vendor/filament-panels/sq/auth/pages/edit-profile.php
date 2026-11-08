<?php

// Albanian for strings Filament's own Albanian translation lacks (merged over the package's file).

return [
    'form' => [
        'password' => [
            'validation_attribute' => 'fjalëkalimi',
        ],
        'password_confirmation' => [
            'validation_attribute' => 'konfirmimi i fjalëkalimit',
        ],
        'current_password' => [
            'label' => 'Fjalëkalimi aktual',
            'below_content' => 'Për siguri, konfirmoni fjalëkalimin për të vazhduar.',
            'validation_attribute' => 'fjalëkalimi aktual',
        ],
    ],
    'multi_factor_authentication' => [
        'label' => 'Verifikimi me dy hapa (2FA)',
    ],
    'notifications' => [
        'email_change_verification_sent' => [
            'title' => 'Kërkesa për ndryshimin e adresës email u dërgua',
            'body' => 'Kërkesa për ndryshimin e adresës email u dërgua te :email. Kontrolloni emailin për ta konfirmuar ndryshimin.',
        ],
        'throttled' => [
            'title' => 'Shumë kërkesa. Provoni përsëri pas :seconds sekondash.',
            'body' => 'Provoni përsëri pas :seconds sekondash.',
        ],
    ],
];
