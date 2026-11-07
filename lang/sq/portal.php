<?php

return [
    'tagline' => 'Monitorim i pavarur i gjykatave të Kosovës',
    'meta_description' => 'Statistika dhe informata anonime nga monitorimi i pavarur i seancave gjyqësore në Kosovë: shtyrjet, përpikëria, transparenca, rezultatet dhe dënimet.',
    'skip' => 'Kalo te përmbajtja',
    'language' => 'Gjuha',
    'show_data' => 'Shfaq të dhënat',
    'days' => 'ditë',
    'suppression_note' => 'Për të mbrojtur privatësinë, çdo numër nën :threshold fshihet dhe shfaqet si “<:threshold”. Publikohen vetëm të dhënat e verifikuara nga redaktorët tanë.',
    'nav' => [
        'label' => 'Navigimi kryesor',
        'menu' => 'Menyja',
        'home' => 'Ballina',
        'cases' => 'Kërko lëndët',
        'publications' => 'Raporte dhe publikime',
        'open_data' => 'Të dhëna të hapura',
        'methodology' => 'Metodologjia',
        'about' => 'Rreth nesh',
        'data_protection' => 'Mbrojtja e të dhënave',
    ],
    'footer' => [
        'about' => 'Program i monitorimit të gjykatave: monitorues të trajnuar ndjekin seancat dhe regjistrojnë vëzhgime të strukturuara e të verifikuara.',
        'privacy' => 'Asnjë emër personal nuk publikohet. Numrat nën :threshold fshihen.',
    ],
    'home' => [
        'kicker' => 'Monitorimi i gjykatave në Kosovë',
        'title' => 'Si i trajtojnë gjykatat e Kosovës lëndët penale, civile, administrative dhe ekonomike?',
        'intro' => 'Monitoruesit tanë ndjekin seancat gjyqësore në të gjithë Kosovën dhe regjistrojnë si zhvillohen ato. Shfletoni rezultatet e verifikuara sipas vitit, gjykatës dhe llojit të lëndës.',
        'cta_cases' => 'Kërko lëndët e monitoruara',
        'cta_data' => 'Shkarko të dhënat e hapura',
        'statistics' => 'Statistikat',
        'latest_reports' => 'Raportet e fundit',
        'all_reports' => 'Të gjitha raportet',
        'updated' => 'Përditësimi i fundit',
        'read_methodology' => 'Lexoni metodologjinë e plotë',
    ],
    'filters' => [
        'year' => 'Viti',
        'all_years' => 'Të gjitha vitet',
        'court' => 'Gjykata',
        'all_courts' => 'Të gjitha gjykatat',
        'type' => 'Lloji i lëndës',
        'all_types' => 'Të gjitha llojet',
        'category' => 'Kategoria e veprës',
        'outcome' => 'Aktgjykimi',
        'all' => 'Të gjitha',
        'reset' => 'Rivendos',
    ],
    'stats' => [
        'cases' => 'Lëndë të monitoruara',
        'hearings' => 'Seanca të monitoruara',
        'postponement_rate' => 'Seanca të shtyra',
        'late_rate' => 'Filluan mbi 15 min vonë',
        'public_rate' => 'Seanca publike',
        'conviction_rate' => 'Dënime në aktgjykime',
    ],
    'charts' => [
        'hearings_by_year' => 'Seanca të monitoruara për vit',
        'cases_by_type' => 'Lëndët e monitoruara sipas llojit',
        'postponement_by_court' => 'Pjesa e seancave të shtyra, sipas gjykatës (%)',
        'postponement_reasons' => 'Arsyet e shtyrjes',
        'verdicts' => 'Aktgjykimet e shkallës së parë',
        'durations' => 'Mediana e ditëve ndërmjet fazave',
    ],
    'cases' => [
        'title' => 'Kërko lëndët e monitoruara',
        'intro' => 'Informata anonime për lëndët e monitoruara dhe të verifikuara. Nuk shfaqen emrat e palëve, gjyqtarëve ose prokurorëve, as numrat e lëndëve; datat jepen vetëm me muaj dhe vit.',
        'search' => 'Vepra ose neni',
        'search_placeholder' => 'p.sh. ryshfet ose 414',
        'found' => '{0} Nuk u gjet asnjë lëndë|{1} U gjet :count lëndë|[2,*] U gjetën :count lëndë',
        'no_offence' => 'Vepra nuk është regjistruar',
        'stage' => 'Faza e fundit',
        'outcome' => 'Aktgjykimi',
        'hearings' => 'Seanca të monitoruara',
        'postponed' => 'Të shtyra',
        'back' => 'Kthehu te kërkimi',
        'offences' => 'Veprat penale',
        'timeline' => 'Rrjedha',
        'monitored_hearings' => 'Seancat e monitoruara',
        'hearing_total' => 'Gjithsej',
        'hearing_postponed' => 'Të shtyra',
        'hearing_late' => 'Me vonesë',
        'hearing_public' => 'Publike',
        'monitored_between' => 'Monitoruar ndërmjet :first dhe :last.',
        'anonymized_note' => 'Kjo faqe është anonime: nuk përmban emra personalë, numra të lëndëve apo data të sakta.',
    ],
    'publications' => [
        'intro' => 'Raporte, përmbledhje politikash dhe sete të dhënash bazuar në gjetjet e monitorimit.',
        'empty' => 'Ende nuk ka publikime.',
        'download' => 'Shkarko raportin (PDF)',
    ],
    'open_data' => [
        'intro' => 'Tabela të përmbledhura në format CSV, të lira për ripërdorim. Çdo shkarkim numërohet në mënyrë anonime.',
        'download' => 'Shkarko CSV',
        'licence' => 'Të dhënat mund t\'i ripërdorni duke cituar programin e monitorimit të gjykatave.',
        'datasets' => [
            'hearings-by-year-and-court' => 'Seancat dhe shtyrjet sipas vitit dhe gjykatës',
            'postponement-by-court' => 'Shkalla e shtyrjes sipas gjykatës',
            'postponement-reasons' => 'Arsyet e shtyrjes',
            'cases-by-type' => 'Lëndët e monitoruara sipas llojit',
            'verdicts' => 'Aktgjykimet e shkallës së parë sipas rezultatit',
            'stage-durations' => 'Mediana e ditëve ndërmjet fazave të lëndës',
        ],
    ],
    'columns' => [
        'year' => 'Viti',
        'court' => 'Gjykata',
        'hearings' => 'Seanca',
        'postponed' => 'Të shtyra',
        'rate' => 'Shkalla (%)',
        'reason' => 'Arsyeja',
        'count' => 'Numri',
        'case_type' => 'Lloji i lëndës',
        'cases' => 'Lëndë',
        'outcome' => 'Rezultati',
        'stages' => 'Fazat',
        'median_days' => 'Mediana e ditëve',
    ],
    'pages' => [
        'methodology' => [
            'title' => 'Metodologjia',
            'intro' => 'Si zgjidhen, vëzhgohen, regjistrohen dhe verifikohen seancat para se të publikohet çdo gjë.',
            'sections' => [
                [
                    'heading' => 'Monitorimi',
                    'paragraphs' => [
                        'Monitorues të trajnuar ndjekin seancat në gjykatat themelore, në Gjykatën e Apelit dhe në Gjykatën Supreme. Ata i ndjekin lëndët nga kallëzimi penal deri te vendimi përfundimtar dhe plotësojnë një pyetësor të strukturuar për çdo seancë.',
                        'Për secilën seancë ata regjistrojnë, ndër të tjera, nëse filloi në kohë, nëse u shty dhe pse, nëse ishte publike, e incizuar dhe me përkthim kur nevojitej, si dhe kush ishte i pranishëm.',
                    ],
                ],
                [
                    'heading' => 'Verifikimi',
                    'paragraphs' => [
                        'Çdo lëndë dhe çdo seancë kalon nëpër shqyrtim: monitoruesi e dorëzon, një redaktor e kontrollon dhe ose e aprovon ose e kthen me koment. Vetëm regjistrat e aprovuar përfshihen në statistika, në kërkimin e lëndëve dhe në të dhënat e hapura.',
                    ],
                ],
                [
                    'heading' => 'Treguesit',
                    'paragraphs' => [
                        'Shkallët llogariten mbi seancat e monitoruara, jo mbi të gjitha seancat e mbajtura në gjykatat e Kosovës. Kohëzgjatjet janë mediana e numrit të ditëve ndërmjet dy fazave të regjistruara të së njëjtës lëndë.',
                    ],
                ],
                [
                    'heading' => 'Mbrojtja e privatësisë',
                    'paragraphs' => [
                        'Statistikat nuk shfaqin kurrë emra. Çdo numër nën pesë fshihet, ndërsa përqindjet dhe medianat që bazohen në më pak se pesë regjistra nuk shfaqen, në mënyrë që individët të mos identifikohen.',
                    ],
                ],
            ],
        ],
        'about' => [
            'title' => 'Rreth programit',
            'intro' => 'Program i pavarur i monitorimit të gjykatave që nxit drejtësi të paanshme, efikase dhe transparente në Kosovë.',
            'sections' => [
                [
                    'heading' => 'Çfarë bëjmë',
                    'paragraphs' => [
                        'Ne vëzhgojmë lëndë penale, civile, administrative dhe ekonomike, me fokus te korrupsioni dhe krimi i organizuar, dhe publikojmë gjetje të verifikuara, raporte dhe rekomandime.',
                    ],
                ],
                [
                    'heading' => 'Kush mund t\'i përdorë të dhënat',
                    'paragraphs' => [
                        'Të gjithë mund t\'i shfletojnë statistikat dhe kërkimin anonim të lëndëve. Hulumtuesit mund të aplikojnë për qasje në të dhëna anonime në nivel regjistri dhe në API në zonën e hulumtuesve, pasi të pranojnë marrëveshjen për përdorimin e të dhënave.',
                    ],
                ],
            ],
        ],
        'data-protection' => [
            'title' => 'Mbrojtja e të dhënave',
            'intro' => 'Si mbrohen të dhënat personale, në përputhje me Ligjin nr. 06/L-082 për Mbrojtjen e të Dhënave Personale dhe, si referencë, me Rregulloren e Përgjithshme të BE-së për Mbrojtjen e të Dhënave (GDPR).',
            'sections' => [
                [
                    'heading' => 'Çfarë përpunojmë',
                    'paragraphs' => [
                        'Monitoruesit regjistrojnë vëzhgime për seancat gjyqësore. Kur nevojitet për monitorim, baza e brendshme e të dhënave përmban emrat e palëve; këta ruhen të enkriptuar dhe janë të qasshëm vetëm për stafin e autorizuar.',
                        'Faqet publike, të dhënat e hapura, zona e hulumtuesve dhe API-ja nuk përmbajnë kurrë emra personalë, numra të lëndëve apo data të sakta të lëndëve individuale.',
                    ],
                ],
                [
                    'heading' => 'Vizitorët e kësaj faqeje',
                    'paragraphs' => [
                        'Kjo faqe nuk përdor reklama apo gjurmim nga palë të treta dhe nuk ngarkon shkronja apo skripta nga shërbime të tjera. Shkarkimet e të dhënave të hapura numërohen pa identifikuar vizitorin.',
                    ],
                ],
                [
                    'heading' => 'Siguria',
                    'paragraphs' => [
                        'Llogaritë e stafit mbrohen me vërtetim me dy faktorë, çdo ndryshim dhe eksport regjistrohet në regjistrin e auditimit, lidhjet janë të enkriptuara dhe kopjet rezervë enkriptohen dhe ruhen jashtë serverit.',
                    ],
                ],
                [
                    'heading' => 'Të drejtat tuaja',
                    'paragraphs' => [
                        'Mund të pyesni nëse përpunohen të dhëna për ju dhe të kërkoni korrigjimin ose fshirjen kur e lejon ligji, duke kontaktuar zyrtarin për mbrojtjen e të dhënave të programit. Mund të ankoheni edhe në Agjencinë për Informim dhe Privatësi.',
                    ],
                ],
            ],
        ],
    ],
];
