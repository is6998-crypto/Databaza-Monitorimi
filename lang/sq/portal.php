<?php

return [
    'tagline' => 'Monitorim i pavarur i gjykatave të Kosovës',
    'meta_description' => 'Pasqyrë anonime e lëndëve dhe e seancave gjyqësore të monitoruara në Kosovë: sa lëndë janë monitoruar, sipas vitit, gjykatës dhe llojit të lëndës.',
    'skip' => 'Kalo te përmbajtja',
    'language' => 'Gjuha',
    'show_data' => 'Shfaq të dhënat',
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
        'sign_in' => 'Hyr',
        'staff' => 'Stafi',
        'staff_hint' => 'Monitoruesit, redaktorët dhe analistët',
        'researchers' => 'Hulumtuesit',
        'researchers_hint' => 'Të dhënat e anonimizuara dhe API',
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
        'statistics' => 'Pasqyra e lëndëve të monitoruara',
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
        'courts' => 'Gjykata të përfshira',
    ],
    'charts' => [
        'cases_by_year' => 'Lëndët e monitoruara për vit',
        'cases_by_court' => 'Lëndët e monitoruara sipas gjykatës',
        'cases_by_type' => 'Lëndët e monitoruara sipas llojit',
        'cases_by_category' => 'Lëndët e monitoruara sipas kategorisë së veprës penale',
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
            'cases-by-year-and-court' => 'Lëndët dhe seancat e monitoruara sipas vitit dhe gjykatës',
            'cases-by-court' => 'Lëndët e monitoruara sipas gjykatës',
            'cases-by-type' => 'Lëndët e monitoruara sipas llojit',
            'cases-by-category' => 'Lëndët e monitoruara sipas kategorisë së veprës penale',
        ],
    ],
    'columns' => [
        'year' => 'Viti',
        'court' => 'Gjykata',
        'hearings' => 'Seanca',
        'case_type' => 'Lloji i lëndës',
        'category' => 'Kategoria e veprës penale',
        'cases' => 'Lëndë',
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
                        'Çdo lëndë dhe çdo seancë kalon nëpër shqyrtim: monitoruesi e dorëzon, një redaktor e kontrollon dhe ose e aprovon ose e kthen me koment. Vetëm regjistrat e aprovuar përfshihen në pasqyrë, në kërkimin e lëndëve dhe në të dhënat e hapura.',
                    ],
                ],
                [
                    'heading' => 'Pasqyra',
                    'paragraphs' => [
                        'Portali publikon një pasqyrë të lëndëve të monitoruara: sa lëndë dhe seanca janë monitoruar, dhe si ndahen sipas vitit, gjykatës, llojit të lëndës dhe kategorisë së veprës penale. Një lëndë numërohet në çdo vit në të cilin është monitoruar të paktën një seancë e saj.',
                        'Gjetjet e monitorimit (shtyrjet, vonesat, transparenca, vendimet) analizohen në raportet tona dhe u vihen në dispozicion hulumtuesve të aprovuar.',
                    ],
                ],
                [
                    'heading' => 'Mbrojtja e privatësisë',
                    'paragraphs' => [
                        'Pasqyra nuk shfaq kurrë emra. Çdo numër lëndësh ose seancash nën pesë fshihet, në mënyrë që individët të mos identifikohen.',
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
