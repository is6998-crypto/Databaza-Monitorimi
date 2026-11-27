<?php

return [
    'courts' => [
        'singular' => 'Sud',
        'plural' => 'Sudovi',
    ],
    'court_branches' => [
        'singular' => 'Ogranak ili veće suda',
        'plural' => 'Ogranci i veća sudova',
        'navigation' => 'Ogranci i veća',
    ],
    'departments' => [
        'singular' => 'Odeljenje',
        'plural' => 'Odeljenja',
    ],
    'divisions' => [
        'singular' => 'Odsek',
        'plural' => 'Odseci',
    ],
    'prosecution_offices' => [
        'singular' => 'Tužilaštvo',
        'plural' => 'Tužilaštva',
    ],
    'judges' => [
        'singular' => 'Sudija',
        'plural' => 'Sudije',
    ],
    'prosecutors' => [
        'singular' => 'Tužilac',
        'plural' => 'Tužioci',
    ],
    'monitors' => [
        'singular' => 'Monitor',
        'plural' => 'Monitori',
    ],
    'applicants' => [
        'singular' => 'Podnosilac (krivične prijave)',
        'plural' => 'Podnosioci',
    ],
    'criminal_codes' => [
        'singular' => 'Krivični zakonik',
        'plural' => 'Krivični zakonici',
    ],
    'criminal_offences' => [
        'singular' => 'Krivično delo',
        'plural' => 'Krivična dela',
    ],
    'categories' => [
        'singular' => 'Kategorija dela',
        'plural' => 'Kategorije dela',
    ],
    'nationalities' => [
        'singular' => 'Nacionalnost',
        'plural' => 'Nacionalnosti',
    ],
    'defendant_positions' => [
        'singular' => 'Funkcija',
        'plural' => 'Funkcije',
    ],
    'economic_statuses' => [
        'singular' => 'Radni status',
        'plural' => 'Radni statusi',
    ],
    'security_measure_types' => [
        'singular' => 'Vrsta mere obezbeđenja',
        'plural' => 'Vrste mera obezbeđenja',
    ],
    'fields' => [
        'name' => 'Naziv',
        'name_sq' => 'Naziv (albanski)',
        'name_en' => 'Naziv (engleski)',
        'name_sr' => 'Naziv (srpski)',
        'title_sq' => 'Naslov (albanski)',
        'title_en' => 'Naslov (engleski)',
        'title_sr' => 'Naslov (srpski)',
        'is_active' => 'Aktivno',
        'updated_at' => 'Ažurirano',
        'code' => 'Šifra',
        'level' => 'Nivo',
        'city' => 'Grad',
        'court' => 'Sud',
        'department' => 'Odeljenje',
        'prosecution_office' => 'Tužilaštvo',
        'user' => 'Korisnički nalog',
        'phone' => 'Telefon',
        'courts' => 'Dodeljeni sudovi',
        'year' => 'Godina',
        'criminal_code' => 'Krivični zakonik',
        'article_number' => 'Član',
        'chapter' => 'Glava',
        'category' => 'Kategorija',
        'category_code' => 'Šifra kategorije',
        'offences_count' => 'Broj dela',
        'departments' => 'Odeljenja',
    ],
    'help' => [
        'serbian' => 'Prikazuje se kada je interfejs na srpskom; bez njega se prikazuje albanski naziv.',
        'departments' => 'U predmetu se nude samo ova odeljenja. Ostavite prazno da dozvolite bilo koje odeljenje.',
    ],
    'actions' => [
        'deactivate' => 'Deaktiviraj izabrano',
    ],
    'merge' => [
        'action' => 'Spoji duplikate',
        'heading' => 'Spoji duplirane zapise',
        'description' => 'Svi predmeti, ročišta i drugi zapisi koji koriste izabrane duplikate biće preusmereni na zapis koji zadržavate. Duplikati se zatim deaktiviraju i premeštaju u korpu.',
        'keep' => 'Zapis koji se zadržava',
        'submit' => 'Spoji',
        'need_two' => 'Izaberite bar dva zapisa za spajanje.',
        'done' => 'Spojeno :count duplikat(a) u „:name”.',
        'repointed' => 'Ažurirano je :count povezanih zapisa.',
    ],
    'import' => [
        'action' => 'Uvezi CSV',
        'template' => 'CSV šablon',
        'file' => 'CSV datoteka',
        'description' => 'Prvi red mora biti zaglavlje. Kolone: :columns. Postojeći redovi se prepoznaju i ažuriraju; novi se kreiraju.',
        'done' => 'Uvoz završen: :created kreirano, :updated ažurirano, :skipped preskočeno.',
        'failed' => 'Datoteka nije mogla da se uveze.',
        'missing_columns' => 'Nedostaju obavezne kolone: :columns',
        'row_error' => 'Red :line: :message',
        'unknown_reference' => 'Red :line: nepoznato :column „:value”.',
    ],
    'catalogue' => [
        'synced' => 'Lista dela učitana: :codes zakonika, :categories kategorija, :offences dela (:created novih, :deactivated deaktivirano).',
    ],
];
