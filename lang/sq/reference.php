<?php

return [
    'courts' => [
        'singular' => 'Gjykatë',
        'plural' => 'Gjykatat',
    ],
    'court_branches' => [
        'singular' => 'Degë e gjykatës',
        'plural' => 'Degët e gjykatave',
    ],
    'departments' => [
        'singular' => 'Departament',
        'plural' => 'Departamentet',
    ],
    'divisions' => [
        'singular' => 'Divizion',
        'plural' => 'Divizionet',
    ],
    'prosecution_offices' => [
        'singular' => 'Prokurori',
        'plural' => 'Prokuroritë',
    ],
    'judges' => [
        'singular' => 'Gjyqtar',
        'plural' => 'Gjyqtarët',
    ],
    'prosecutors' => [
        'singular' => 'Prokuror',
        'plural' => 'Prokurorët',
    ],
    'monitors' => [
        'singular' => 'Monitorues',
        'plural' => 'Monitoruesit',
    ],
    'applicants' => [
        'singular' => 'Parashtrues (kallëzimi penal)',
        'plural' => 'Parashtruesit',
    ],
    'criminal_codes' => [
        'singular' => 'Kod penal',
        'plural' => 'Kodet penale',
    ],
    'criminal_offences' => [
        'singular' => 'Vepër penale',
        'plural' => 'Veprat penale',
    ],
    'categories' => [
        'singular' => 'Kategori e veprës',
        'plural' => 'Kategoritë e veprave',
    ],
    'nationalities' => [
        'singular' => 'Kombësi',
        'plural' => 'Kombësitë',
    ],
    'defendant_positions' => [
        'singular' => 'Pozita e mbajtur',
        'plural' => 'Pozitat e mbajtura',
    ],
    'economic_statuses' => [
        'singular' => 'Statusi i punësimit',
        'plural' => 'Statuset e punësimit',
    ],
    'security_measure_types' => [
        'singular' => 'Lloj i masës së sigurisë',
        'plural' => 'Llojet e masave të sigurisë',
    ],
    'fields' => [
        'name' => 'Emri',
        'name_sq' => 'Emri (shqip)',
        'name_en' => 'Emri (anglisht)',
        'name_sr' => 'Emri (serbisht)',
        'title_sq' => 'Titulli (shqip)',
        'title_en' => 'Titulli (anglisht)',
        'title_sr' => 'Titulli (serbisht)',
        'is_active' => 'Aktiv',
        'updated_at' => 'Përditësuar',
        'code' => 'Kodi',
        'level' => 'Niveli',
        'city' => 'Qyteti',
        'court' => 'Gjykata',
        'department' => 'Departamenti',
        'prosecution_office' => 'Prokuroria',
        'user' => 'Llogaria e përdoruesit',
        'phone' => 'Telefoni',
        'courts' => 'Gjykatat e caktuara',
        'year' => 'Viti',
        'criminal_code' => 'Kodi penal',
        'article_number' => 'Neni',
        'chapter' => 'Kapitulli',
        'category' => 'Kategoria',
        'category_code' => 'Kodi i kategorisë',
        'offences_count' => 'Numri i veprave',
    ],
    'help' => [
        'serbian' => 'Opsionale — ndërfaqja është e gatshme për serbisht.',
    ],
    'actions' => [
        'deactivate' => 'Çaktivizo të zgjedhurat',
    ],
    'merge' => [
        'action' => 'Bashko dublikatat',
        'heading' => 'Bashko regjistrat e dyfishtë',
        'description' => 'Të gjitha lëndët, seancat dhe regjistrat e tjerë që përdorin dublikatat e zgjedhura do të lidhen me regjistrin që mbani. Dublikatat më pas çaktivizohen dhe zhvendosen në kosh.',
        'keep' => 'Regjistri që mbahet',
        'submit' => 'Bashko',
        'need_two' => 'Zgjidhni së paku dy regjistra për t\'i bashkuar.',
        'done' => 'U bashkuan :count dublikatë në ":name".',
        'repointed' => 'U përditësuan :count regjistra të lidhur.',
    ],
    'import' => [
        'action' => 'Importo CSV',
        'template' => 'Shablloni CSV',
        'file' => 'Skedari CSV',
        'description' => 'Rreshti i parë duhet të jetë koka. Kolonat: :columns. Rreshtat ekzistues përditësohen; të rinjtë krijohen.',
        'done' => 'Importi përfundoi: :created të krijuara, :updated të përditësuara, :skipped të anashkaluara.',
        'failed' => 'Skedari nuk mund të importohej.',
        'missing_columns' => 'Mungojnë kolonat e detyrueshme: :columns',
        'row_error' => 'Rreshti :line: :message',
        'unknown_reference' => 'Rreshti :line: :column ":value" i panjohur.',
    ],
    'catalogue' => [
        'synced' => 'Lista e veprave u ngarkua: :codes kode, :categories kategori, :offences vepra (:created të reja, :deactivated u çaktivizuan).',
    ],
];
