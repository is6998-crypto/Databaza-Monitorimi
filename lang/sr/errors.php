<?php

return [
    'home' => 'Idi na početnu stranicu',
    'back' => 'Nazad',
    '401' => ['title' => 'Prijavite se', 'message' => 'Morate se prijaviti da biste videli ovu stranicu.'],
    '403' => ['title' => 'Pristup odbijen', 'message' => 'Nemate dozvolu da vidite ovu stranicu.'],
    '404' => ['title' => 'Stranica nije pronađena', 'message' => 'Stranica koju tražite ne postoji ili je premeštena.'],
    '419' => ['title' => 'Sesija je istekla', 'message' => 'Vaša sesija je istekla. Ponovo učitajte stranicu i pokušajte ponovo.'],
    '429' => ['title' => 'Previše zahteva', 'message' => 'Poslali ste previše zahteva. Sačekajte trenutak i pokušajte ponovo.'],
    '500' => ['title' => 'Nešto nije u redu', 'message' => 'Problem je zabeležen. Pokušajte ponovo kasnije.'],
    '503' => ['title' => 'Održavanje u toku', 'message' => 'Platforma se ažurira. Pokušajte ponovo za nekoliko minuta.'],
    'alert' => [
        'subject' => 'Greška na platformi: :error',
        'intro' => 'Platforma je naišla na grešku. Detalji ispod ne sadrže lične podatke; ista greška se prijavljuje najviše jednom na sat.',
        'outro' => 'Pogledajte dnevnik aplikacije (storage/logs) za sve detalje.',
        'fields' => [
            'error' => 'Greška',
            'message' => 'Poruka',
            'where' => 'Gde u kodu',
            'page' => 'Stranica',
            'user' => 'Korisnik',
            'environment' => 'Okruženje',
            'time' => 'Vreme',
        ],
    ],
];
