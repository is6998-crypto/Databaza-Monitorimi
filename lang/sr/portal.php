<?php

return [
    'tagline' => 'Nezavisni monitoring sudova na Kosovu',
    'meta_description' => 'Anonimizovani pregled sudskih predmeta i ročišta praćenih na Kosovu: koliko je predmeta praćeno, po godini, sudu i vrsti predmeta.',
    'skip' => 'Preskoči na sadržaj',
    'language' => 'Jezik',
    'show_data' => 'Prikaži podatke',
    'suppression_note' => 'Radi zaštite privatnosti, svaki broj manji od :threshold je sakriven i prikazan kao „<:threshold”. Objavljuju se samo podaci koje su proverili naši urednici.',
    'nav' => [
        'label' => 'Glavna navigacija',
        'menu' => 'Meni',
        'home' => 'Početna',
        'cases' => 'Pretraga predmeta',
        'publications' => 'Izveštaji i publikacije',
        'open_data' => 'Otvoreni podaci',
        'methodology' => 'Metodologija',
        'about' => 'O programu',
        'data_protection' => 'Zaštita podataka',
        'sign_in' => 'Prijava',
        'staff' => 'Osoblje',
        'staff_hint' => 'Monitori, urednici i analitičari',
        'researchers' => 'Istraživači',
        'researchers_hint' => 'Anonimizovani skupovi podataka i API',
    ],
    'footer' => [
        'about' => 'Program monitoringa sudova: obučeni monitori prisustvuju ročištima i beleže strukturisana, proverena zapažanja.',
        'privacy' => 'Lična imena se nikada ne objavljuju. Brojevi manji od :threshold su sakriveni.',
    ],
    'home' => [
        'kicker' => 'Monitoring sudova na Kosovu',
        'title' => 'Kako sudovi na Kosovu postupaju u krivičnim, građanskim, upravnim i privrednim predmetima?',
        'intro' => 'Naši monitori prisustvuju sudskim ročištima širom Kosova i beleže kako se vode. Istražite proverene rezultate po godini, sudu i vrsti predmeta.',
        'cta_cases' => 'Pretraži praćene predmete',
        'cta_data' => 'Preuzmi otvorene podatke',
        'statistics' => 'Pregled praćenih predmeta',
        'latest_reports' => 'Najnoviji izveštaji',
        'all_reports' => 'Svi izveštaji',
        'updated' => 'Poslednje ažuriranje',
        'read_methodology' => 'Pročitajte celu metodologiju',
    ],
    'filters' => [
        'year' => 'Godina',
        'all_years' => 'Sve godine',
        'court' => 'Sud',
        'all_courts' => 'Svi sudovi',
        'type' => 'Vrsta predmeta',
        'all_types' => 'Sve vrste',
        'category' => 'Kategorija dela',
        'all' => 'Sve',
        'reset' => 'Poništi',
    ],
    'stats' => [
        'cases' => 'Praćeni predmeti',
        'hearings' => 'Praćena ročišta',
        'courts' => 'Obuhvaćeni sudovi',
    ],
    'charts' => [
        'cases_by_year' => 'Praćeni predmeti po godini',
        'cases_by_court' => 'Praćeni predmeti po sudu',
        'cases_by_type' => 'Praćeni predmeti po vrsti',
        'cases_by_category' => 'Praćeni predmeti po kategoriji dela',
    ],
    'cases' => [
        'title' => 'Pretraga praćenih predmeta',
        'intro' => 'Anonimizovani podaci o proverenim praćenim predmetima. Ne prikazuju se imena stranaka, sudija ili tužilaca ni brojevi predmeta; datumi su svedeni na mesec i godinu.',
        'search' => 'Delo ili član',
        'search_placeholder' => 'npr. mito ili 414',
        'found' => '{0} Nije pronađen nijedan predmet|{1} Pronađen je :count predmet|[2,4] Pronađena su :count predmeta|[5,*] Pronađeno je :count predmeta',
        'no_offence' => 'Delo nije uneto',
        'stage' => 'Poslednja faza',
        'hearings' => 'Praćena ročišta',
        'back' => 'Nazad na pretragu predmeta',
        'offences' => 'Dela',
        'timeline' => 'Hronologija',
        'monitored_hearings' => 'Praćena ročišta',
        'hearing_total' => 'Ukupno',
        'monitored_between' => 'Praćeno između :first i :last.',
        'anonymized_note' => 'Ova stranica je anonimizovana: ne sadrži lična imena, brojeve predmeta ni tačne datume.',
    ],
    'publications' => [
        'intro' => 'Izveštaji, analize politika i skupovi podataka zasnovani na nalazima monitoringa.',
        'empty' => 'Još nema publikacija.',
        'download' => 'Preuzmi izveštaj (PDF)',
    ],
    'open_data' => [
        'intro' => 'Zbirne tabele u CSV formatu, slobodne za ponovnu upotrebu. Svako preuzimanje se broji anonimno.',
        'download' => 'Preuzmi CSV',
        'licence' => 'Podatke možete ponovo koristiti uz navođenje programa monitoringa sudova kao izvora.',
        'datasets' => [
            'cases-by-year-and-court' => 'Praćeni predmeti i ročišta po godini i sudu',
            'cases-by-court' => 'Praćeni predmeti po sudu',
            'cases-by-type' => 'Praćeni predmeti po vrsti',
            'cases-by-category' => 'Praćeni predmeti po kategoriji dela',
        ],
    ],
    'columns' => [
        'year' => 'Godina',
        'court' => 'Sud',
        'hearings' => 'Ročišta',
        'case_type' => 'Vrsta predmeta',
        'category' => 'Kategorija dela',
        'cases' => 'Predmeti',
    ],
    'pages' => [
        'methodology' => [
            'title' => 'Metodologija',
            'intro' => 'Kako se ročišta biraju, posmatraju, beleže i proveravaju pre nego što se bilo šta objavi.',
            'sections' => [
                [
                    'heading' => 'Monitoring',
                    'paragraphs' => [
                        'Obučeni monitori prisustvuju ročištima u osnovnim sudovima, Apelacionom sudu i Vrhovnom sudu. Prate predmete od krivične prijave do pravosnažne odluke i za svako ročište kojem prisustvuju popunjavaju strukturisani upitnik.',
                        'Za svako ročište beleže, između ostalog, da li je počelo na vreme, da li je odloženo i zašto, da li je bilo javno, snimljeno i prevođeno kada je bilo potrebno, i ko je bio prisutan.',
                    ],
                ],
                [
                    'heading' => 'Provera',
                    'paragraphs' => [
                        'Svaki predmet i svako ročište prolaze pregled: monitor ga šalje, urednik ga proverava i odobrava ili vraća sa komentarom. U pregled, pretragu predmeta i otvorene podatke ulaze samo odobreni zapisi.',
                    ],
                ],
                [
                    'heading' => 'Pregled',
                    'paragraphs' => [
                        'Portal objavljuje pregled praćenih predmeta: koliko je predmeta i ročišta praćeno i kako se dele po godini, sudu, vrsti predmeta i kategoriji dela. Predmet se računa u svakoj godini u kojoj je praćeno bar jedno njegovo ročište.',
                        'Nalazi monitoringa (odlaganja, kašnjenja, transparentnost, odluke) analiziraju se u našim izveštajima i dostupni su odobrenim istraživačima.',
                    ],
                ],
                [
                    'heading' => 'Zaštita privatnosti',
                    'paragraphs' => [
                        'Pregled nikada ne prikazuje imena. Svaki broj predmeta ili ročišta manji od pet je sakriven, kako se pojedinci ne bi mogli identifikovati.',
                    ],
                ],
            ],
        ],
        'about' => [
            'title' => 'O programu',
            'intro' => 'Nezavisni program monitoringa sudova koji promoviše pravično, efikasno i transparentno pravosuđe na Kosovu.',
            'sections' => [
                [
                    'heading' => 'Šta radimo',
                    'paragraphs' => [
                        'Posmatramo krivične, građanske, upravne i privredne predmete, sa fokusom na korupciju i organizovani kriminal, i objavljujemo proverene nalaze, izveštaje i preporuke.',
                    ],
                ],
                [
                    'heading' => 'Ko može da koristi podatke',
                    'paragraphs' => [
                        'Svako može da istražuje statistiku i anonimizovanu pretragu predmeta. Istraživači mogu da zatraže pristup anonimizovanim podacima na nivou zapisa i API-ju u oblasti za istraživače, nakon prihvatanja sporazuma o korišćenju podataka.',
                    ],
                ],
            ],
        ],
        'data-protection' => [
            'title' => 'Zaštita podataka',
            'intro' => 'Kako se štite lični podaci, u skladu sa Zakonom br. 06/L-082 o zaštiti ličnih podataka i, kao referencom, Opštom uredbom EU o zaštiti podataka (GDPR).',
            'sections' => [
                [
                    'heading' => 'Šta obrađujemo',
                    'paragraphs' => [
                        'Monitori beleže zapažanja o sudskim ročištima. Kada je to potrebno za monitoring, interna baza sadrži imena stranaka; ona se čuvaju šifrovano i dostupna su samo ovlašćenom osoblju.',
                        'Javne stranice, otvoreni podaci, oblast za istraživače i API nikada ne sadrže lična imena, brojeve predmeta ni tačne datume pojedinačnih predmeta.',
                    ],
                ],
                [
                    'heading' => 'Posetioci ovog veb-sajta',
                    'paragraphs' => [
                        'Ovaj veb-sajt ne koristi oglašavanje ni praćenje trećih strana i ne učitava fontove ili skripte sa drugih servisa. Preuzimanja otvorenih podataka broje se bez identifikacije posetioca.',
                    ],
                ],
                [
                    'heading' => 'Bezbednost',
                    'paragraphs' => [
                        'Nalozi osoblja zaštićeni su dvofaktorskom autentifikacijom, svaka promena i izvoz beleže se u revizijskom zapisu, veze su šifrovane, a rezervne kopije su šifrovane i čuvaju se van sedišta.',
                    ],
                ],
                [
                    'heading' => 'Vaša prava',
                    'paragraphs' => [
                        'Možete pitati da li se obrađuju podaci o vama i zatražiti ispravku ili brisanje kada zakon to dozvoljava, obraćanjem službeniku za zaštitu podataka programa. Možete se žaliti i Agenciji za informisanje i privatnost.',
                    ],
                ],
            ],
        ],
    ],
];
