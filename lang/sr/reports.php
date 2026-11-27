<?php

return [
    'model' => 'Izveštaj o ročištu',
    'plural' => 'Izveštaji o ročištima',
    'title' => 'Izveštaj o ročištu od :date — :case',
    'subheading' => 'Za svako ročište monitor piše šta se dogodilo. Urednik ga čita, uređuje i ostavlja komentare.',
    'words' => '{0} Bez teksta|{1} :count reč|[2,4] :count reči|[5,*] :count reči',
    'empty_body' => 'Izveštaj još nema tekst.',
    'sections' => [
        'hearing' => 'Ročište',
        'report' => 'Izveštaj',
        'status' => 'O ovom izveštaju',
    ],
    'fields' => [
        'hearing' => 'Ročište',
        'title' => 'Naslov',
        'body' => 'Šta se dogodilo na ročištu',
        'status' => 'Status',
        'author' => 'Napisao/la',
        'reviewer' => 'Pregledao/la',
        'comments' => 'Komentari',
        'updated_at' => 'Ažurirano',
        'submitted_at' => 'Poslato',
    ],
    'help' => [
        'hearing' => 'Izaberite ročište o kojem pišete. Pretražujte po broju predmeta. Prikazana su samo ročišta bez izveštaja.',
        'report' => 'Opišite kako je ročište proteklo: ko je bio prisutan, šta je rečeno i odlučeno, kada je sledeće ročište. Tekst se čuva šifrovano i ostaje unutar panela.',
    ],
    'filters' => [
        'mine' => 'Moji izveštaji',
    ],
    'actions' => [
        'write' => 'Napiši izveštaj',
        'write_for_hearing' => 'Napiši izveštaj',
        'open_for_hearing' => 'Izveštaj o ročištu',
        'submit' => 'Pošalji uredniku',
        'submit_help' => 'Urednik dobija obaveštenje i može da uredi izveštaj ili ostavi komentare. Možete ga menjati dok ne bude odobren.',
        'approve' => 'Odobri',
        'approve_help' => 'Izveštaj se označava kao završen i monitor više ne može da ga menja.',
        'send_back' => 'Vrati sa komentarom',
        'send_back_help' => 'Monitor vidi vaš komentar, dopunjuje izveštaj i ponovo ga šalje.',
    ],
    'done' => [
        'submitted' => 'Izveštaj je poslat uredniku.',
        'approved' => 'Izveštaj je odobren.',
        'returned' => 'Izveštaj je vraćen monitoru.',
    ],
    'errors' => [
        'empty' => 'Napišite izveštaj pre slanja.',
        'comment_required' => 'Napišite komentar.',
        'exists' => 'Ovo ročište već ima izveštaj.',
    ],
    'comments' => [
        'title' => 'Komentari',
        'description' => 'Razgovor između urednika i monitora o ovom izveštaju.',
        'empty' => 'Još nema komentara.',
        'field' => 'Komentar',
        'placeholder' => 'Napišite komentar…',
        'post' => 'Dodaj komentar',
        'author' => 'Monitor',
        'editor' => 'Urednik',
        'deleted_user' => 'Obrisan korisnik',
    ],
    'empty' => [
        'heading' => 'Još nema izveštaja',
        'description' => 'Posle svakog ročišta napišite kratak izveštaj o tome šta se dogodilo.',
    ],
    'queue' => [
        'title' => 'Izveštaji koji čekaju urednika',
        'empty' => 'Nijedan izveštaj ne čeka',
    ],
    'notifications' => [
        'submitted' => 'Novi izveštaj za pregled: ročište od :date (:case)',
        'returned' => 'Izveštaj vraćen sa komentarom: ročište od :date (:case)',
        'approved' => 'Izveštaj odobren: ročište od :date (:case)',
        'commented' => 'Novi komentar na izveštaj: ročište od :date (:case)',
    ],
];
