<?php

return [
    'home' => 'Shko te ballina',
    'back' => 'Kthehu prapa',
    '401' => ['title' => 'Ju lutemi kyçuni', 'message' => 'Duhet të kyçeni për ta parë këtë faqe.'],
    '403' => ['title' => 'Qasja nuk lejohet', 'message' => 'Nuk keni leje ta shihni këtë faqe.'],
    '404' => ['title' => 'Faqja nuk u gjet', 'message' => 'Faqja që kërkoni nuk ekziston ose është zhvendosur.'],
    '419' => ['title' => 'Sesioni ka skaduar', 'message' => 'Sesioni juaj ka skaduar. Rifreskoni faqen dhe provoni përsëri.'],
    '429' => ['title' => 'Shumë kërkesa', 'message' => 'Keni dërguar shumë kërkesa. Prisni pak dhe provoni përsëri.'],
    '500' => ['title' => 'Diçka shkoi keq', 'message' => 'Problemi u regjistrua. Ju lutemi provoni përsëri më vonë.'],
    '503' => ['title' => 'Në mirëmbajtje', 'message' => 'Platforma po përditësohet. Ju lutemi provoni përsëri pas pak minutash.'],
    'alert' => [
        'subject' => 'Gabim në platformë: :error',
        'intro' => 'Platforma hasi në një gabim. Detajet më poshtë nuk përmbajnë të dhëna personale; i njëjti gabim raportohet së shumti një herë në orë.',
        'outro' => 'Shikoni regjistrin e aplikacionit (storage/logs) për të gjitha detajet.',
        'fields' => [
            'error' => 'Gabimi',
            'message' => 'Mesazhi',
            'where' => 'Ku në kod',
            'page' => 'Faqja',
            'user' => 'Përdoruesi',
            'environment' => 'Mjedisi',
            'time' => 'Koha',
        ],
    ],
];
