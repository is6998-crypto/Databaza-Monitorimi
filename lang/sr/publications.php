<?php

return [
    'model' => 'Publikacija',
    'plural' => 'Izveštaji i publikacije',
    'preview' => 'Pogledaj na portalu',
    'fields' => [
        'title' => 'Naslov',
        'title_sq' => 'Naslov (albanski)',
        'title_en' => 'Naslov (engleski)',
        'slug' => 'Veb adresa',
        'type' => 'Vrsta',
        'summary_sq' => 'Rezime (albanski)',
        'summary_en' => 'Rezime (engleski)',
        'file' => 'PDF datoteka',
        'published_on' => 'Datum objavljivanja',
        'is_published' => 'Objavljeno na portalu',
    ],
    'help' => [
        'is_published' => 'Vidljivo na javnom portalu od datuma objavljivanja.',
    ],
];
