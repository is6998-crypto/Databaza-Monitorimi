<?php

return [
    'model' => 'Korisnik',
    'plural' => 'Korisnici',
    'sections' => [
        'account' => 'Nalog',
        'roles' => 'Uloge i dozvole',
        'monitor' => 'Profil monitora',
    ],
    'fields' => [
        'name' => 'Ime',
        'email' => 'E-pošta',
        'password' => 'Lozinka',
        'locale' => 'Jezik',
        'is_active' => 'Aktivan',
        'roles' => 'Uloge',
        'mfa' => '2FA',
        'last_login_at' => 'Poslednja prijava',
        'monitor_code' => 'Šifra monitora',
        'monitor_phone' => 'Telefon',
        'monitor_courts' => 'Dodeljeni sudovi',
    ],
    'help' => [
        'password_keep' => 'Ostavite prazno da zadržite trenutnu lozinku.',
        'roles' => 'Samo superadministrator može da menja uloge. Svaka promena se beleži u revizijskom zapisu.',
        'monitor' => 'Monitori vide samo predmete u dodeljenim sudovima (i predmete koje su sami uneli ili koji su im dodeljeni).',
    ],
    'actions' => [
        'reset_mfa' => 'Resetuj 2FA',
        'reset_mfa_confirm' => 'Korisnik će pri sledećoj prijavi morati ponovo da podesi dvofaktorsku autentifikaciju.',
        'reset_mfa_done' => 'Dvofaktorska autentifikacija je resetovana.',
    ],
];
