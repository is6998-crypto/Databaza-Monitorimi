<?php

/*
|--------------------------------------------------------------------------
| Legacy import mapping (proposal B8)
|--------------------------------------------------------------------------
|
| How the old MySQL database maps to the new schema. The table and column
| names below describe the *fake fixture* used by the tests; before a real
| run, adjust them to the real legacy schema:
|
|   php artisan legacy:inspect --connection=legacy
|
| lists every legacy table and column (names and row counts only, never
| values). In `columns`, the key is the field in the new system and the value
| is the legacy column (null = the legacy table has no such column).
| See docs/MIGRATION.md for the full procedure.
*/

$hearing = [
    'hearing_date' => 'seanca_data',
    'hearing_type' => 'seanca_lloji',
    'venue' => 'seanca_vendi',
    'is_public' => 'seanca_publike',
    'started_late' => 'filloi_me_vonese',
    'delay_minutes' => 'vonesa_minuta',
    'delay_cause' => 'shkaku_vonese',
    'was_postponed' => 'u_shty',
    'postponement_reasons' => 'arsyeja_shtyrjes',
    'recording' => 'incizimi',
    'interpretation_needed' => 'perkthyes_nevoja',
    'interpretation_provided' => 'perkthyes_siguruar',
    'phone_used' => 'telefon_perdorur',
    'phone_users' => 'telefon_perdoruesit',
    'security_adequate' => 'siguria_adekuate',
    'security_comment' => null,
    'participants' => 'pjesemarresit',
    'notes' => 'shenime',
    'monitor' => 'monitoruesi',
];

$criminal = [
    'columns' => [
        // Case
        'court' => 'gjykata',
        'court_branch' => 'dega',
        'department' => 'departamenti',
        'division' => null,
        'judge' => 'gjyqtari',
        'prosecutor' => 'prokurori',
        'prosecution_office' => 'prokuroria',
        'court_case_number' => 'nr_lendes',
        'indictment_number' => 'nr_pp',
        'police_case_number' => 'nr_policise',
        'summary' => null,

        // The defendant of the row (one legacy row per hearing and defendant)
        'party_name' => 'i_pandehuri',
        'party_gender' => 'gjinia',
        'party_nationality' => 'kombesia',
        'party_position' => 'pozita',
        'party_economic_status' => 'statusi_ekonomik',
        'party_counsel' => 'mbrojtesi',
        'party_counsel_at_police' => 'mbrojtes_ne_polici',
        'injured_party_name' => 'i_demtuari',

        // Charge
        'criminal_code' => 'kodi_penal',
        'article' => 'neni',
        'offence' => 'vepra',

        // Sentence (first instance) and measures
        'sentence_prison_years' => null,
        'sentence_prison_months' => 'denimi_burg_muaj',
        'sentence_suspended_months' => 'denimi_kusht_muaj',
        'sentence_fine' => 'gjoba',
        'requested_prison_months' => null,
        'seizure_requested' => 'sekuestrimi_kerkuar',
        'seizure_granted' => 'sekuestrimi_aprovuar',
        'confiscation_requested' => 'konfiskimi_kerkuar',
        'confiscation_granted' => 'konfiskimi_aprovuar',
        'security_measure' => 'masa_sigurise',
        'security_measure_date' => 'masa_data',

        ...$hearing,
    ],

    // Stage => legacy columns for its date, outcome and appellant.
    'stages' => [
        'criminal_report_filed' => ['date' => 'data_kallzimit'],
        'investigation_opened' => ['date' => 'data_hetimit'],
        'indictment_filed' => ['date' => 'data_aktakuzes'],
        'indictment_decision' => ['date' => 'data_vendimit_aktakuzes', 'outcome' => 'vendimi_aktakuzes'],
        'plea_agreement' => ['outcome' => 'marreveshja_pranimit'],
        'judgment_announced' => ['date' => 'data_aktgjykimit', 'outcome' => 'aktgjykimi'],
        'appeal_filed' => ['date' => 'data_ankeses', 'appellant' => 'ankuesi'],
        'appeal_decision' => ['date' => 'data_vendimit_apelit', 'outcome' => 'vendimi_apelit'],
    ],
];

$civil = [
    'columns' => [
        'court' => 'gjykata',
        'court_branch' => 'dega',
        'department' => 'departamenti',
        'judge' => 'gjyqtari',
        'court_case_number' => 'nr_lendes',
        'plaintiff_name' => 'paditesi',
        'respondent_name' => 'i_padituri',
        'claim_type' => 'lloji_kerkeses',
        'claim_value' => 'vlera_kerkeses',
        'filed_date' => 'data_padise',
        'plaintiff_type' => 'lloji_paditesit',
        'respondent_type' => 'lloji_te_paditurit',
        'representation' => 'perfaqesimi',
        'legal_aid' => 'ndihme_juridike',
        'mediation_attempted' => 'ndermjetesim',
        'civil_outcome' => 'rezultati',
        'decision_date' => 'data_vendimit',
        ...$hearing,
    ],
    'stages' => [],
];

$administrative = [
    'columns' => [
        'court' => 'gjykata',
        'department' => 'departamenti',
        'judge' => 'gjyqtari',
        'court_case_number' => 'nr_lendes',
        'plaintiff_name' => 'paditesi',
        'plaintiff_type' => 'lloji_paditesit',
        'respondent_body' => 'organi_paditur',
        'first_instance_decision' => 'vendimi_shkalles_pare',
        'first_instance_decision_date' => 'data_vendimit_shkalles_pare',
        'decision_reasoned' => 'vendim_i_arsyetuar',
        'decision_received_date' => 'data_pranimit_vendimit',
        'legal_remedy_type' => 'mjeti_juridik',
        'legal_remedy_date' => 'data_mjetit_juridik',
        'second_instance_outcome' => 'rezultati_shkalles_dyte',
        'second_instance_decision_date' => 'data_vendimit_shkalles_dyte',
        'conflict_filed' => 'konflikt_administrativ',
        'conflict_filed_date' => 'data_konfliktit',
        'representation' => 'perfaqesimi',
        'first_review_date' => 'data_shqyrtimit_pare',
        'last_review_date' => 'data_shqyrtimit_fundit',
        ...$hearing,
    ],
    'stages' => [],
];

return [
    // Name of the database connection that points at the legacy database (config/database.php).
    'connection' => env('LEGACY_DB_CONNECTION_NAME', 'legacy'),

    // Primary key of every legacy table.
    'id_column' => 'id',

    // Legacy columns that are bookkeeping only and are not reported as "unmapped".
    'ignore_columns' => ['id', 'created_at', 'updated_at', 'deleted_at', 'user_id', 'created_by', 'updated_by', 'remember_token'],

    // Workflow status of imported records. "approved" makes historical data count in
    // dashboards, the portal and research; use "submitted" to have editors re-review it.
    'status' => env('LEGACY_IMPORT_STATUS', 'approved'),

    // Rows that share any of these numbers (in the same module and court) are one case.
    'group_by' => ['court_case_number', 'indictment_number'],

    // Stage of the charges read from a row (criminal_report, indictment or judgment).
    'charge_stage' => 'indictment',

    // Case groups processed per batch, and legacy rows read per query.
    'batch_size' => 200,

    // Where reconciliation reports are written (never commit them).
    'reports_path' => storage_path('app/private/legacy'),

    // Criminal code used when the row does not say, by the date of the first stage.
    'criminal_codes_by_date' => [
        ['from' => '2019-04-14', 'code' => 'KPRK-2019'],
        ['from' => '2013-01-01', 'code' => 'KPK-2012'],
        ['from' => null, 'code' => 'UNMIK-2003/25'],
    ],

    'modules' => [
        'criminal_corruption' => ['table' => 'lendet_korrupsioni', 'case_type' => 'criminal_corruption', 'label' => 'Criminal – Corruption', 'defaults' => ['department' => 'SER'], ...$criminal],
        'criminal_general' => ['table' => 'lendet_pergjithshme', 'case_type' => 'criminal_general', 'label' => 'Criminal – General Department', 'defaults' => ['department' => 'GEN'], ...$criminal],
        'criminal_serious' => ['table' => 'lendet_krime_te_renda', 'case_type' => 'criminal_serious', 'label' => 'Criminal – Serious Crimes', 'defaults' => ['department' => 'SER'], ...$criminal],
        'criminal_special' => ['table' => 'lendet_speciale', 'case_type' => 'criminal_special', 'label' => 'Criminal – Special Department', 'defaults' => ['department' => 'SPE'], ...$criminal],
        'civil' => ['table' => 'lendet_civile', 'case_type' => 'civil', 'label' => 'Civil', 'defaults' => ['department' => 'GEN'], ...$civil],
        'administrative' => ['table' => 'lendet_administrative', 'case_type' => 'administrative', 'label' => 'Administrative Department', 'defaults' => ['department' => 'ADM'], ...$administrative],
        'economic' => ['table' => 'lendet_ekonomike', 'case_type' => 'economic', 'label' => 'Economic Department', 'defaults' => ['department' => 'COM'], ...$civil],
    ],

    /*
    | Extra spellings for reference data, matched after normalisation (lower case,
    | no diacritics or punctuation). Values are codes (courts, departments,
    | criminal codes) or e-mail addresses (monitors).
    */
    'aliases' => [
        'court' => [
            'gjth pr' => 'GJTH-PR', 'gjth prishtine' => 'GJTH-PR', 'gj th prishtine' => 'GJTH-PR',
            'gjth pz' => 'GJTH-PZ', 'gjth pe' => 'GJTH-PE', 'gjth gjk' => 'GJTH-GJK',
            'gjth mi' => 'GJTH-MI', 'gjth fe' => 'GJTH-FE', 'gjth gj' => 'GJTH-GJ',
            'apeli' => 'GJA', 'gjykata e apelit' => 'GJA', 'supremja' => 'GJS',
        ],
        'department' => [
            'krime te renda' => 'SER', 'departamenti per krime te renda' => 'SER', 'dkr' => 'SER',
            'pergjithshem' => 'GEN', 'departamenti i pergjithshem' => 'GEN',
            'special' => 'SPE', 'departamenti special' => 'SPE',
            'administrativ' => 'ADM', 'ekonomik' => 'COM', 'te mitur' => 'JUV',
        ],
        'criminal_code' => [
            'kprk' => 'KPRK-2019', 'kprk 2019' => 'KPRK-2019', 'kodi penal 2019' => 'KPRK-2019', '06 l 074' => 'KPRK-2019',
            'kpk' => 'KPK-2012', 'kpk 2012' => 'KPK-2012', 'kodi penal 2012' => 'KPK-2012', '04 l 082' => 'KPK-2012',
            'kppk' => 'UNMIK-2003/25', 'unmik' => 'UNMIK-2003/25', 'kodi i perkohshem penal' => 'UNMIK-2003/25',
        ],
        'monitor' => [
            // 'emri mbiemri' => 'monitor@example.org',
        ],
    ],

    /*
    | Free text → codes. Every enum also matches its own value and its Albanian and
    | English labels automatically; list only additional spellings here (keys are
    | normalised the same way: "Mungesa e të akuzuarit" = "mungesa e te akuzuarit").
    */
    'values' => [
        'boolean' => [
            'po' => true, 'p' => true, 'yes' => true, 'y' => true, 'true' => true, '1' => true, 'ka' => true, 'x' => true, 'publike' => true, 'e hapur' => true,
            'jo' => false, 'j' => false, 'no' => false, 'n' => false, 'false' => false, '0' => false, 'nuk ka' => false, 'e mbyllur' => false, 'jopublike' => false,
        ],
        'gender' => [
            'm' => 'male', 'mashkull' => 'male', 'mashkulli' => 'male',
            'f' => 'female', 'femer' => 'female', 'femra' => 'female', 'z' => 'female',
            'person juridik' => 'legal_person', 'pj' => 'legal_person', 'kompani' => 'legal_person',
        ],
        'counsel_type' => [
            'i autorizuar' => 'retained', 'mbrojtes i autorizuar' => 'retained', 'privat' => 'retained',
            'sipas detyres zyrtare' => 'ex_officio', 'ex officio' => 'ex_officio', 'zyrtar' => 'ex_officio',
            'pa mbrojtes' => 'none', 'nuk ka' => 'none', 'vetembrojtje' => 'none',
        ],
        'hearing_type' => [
            'seanca fillestare' => 'initial', 'shqyrtimi fillestar' => 'initial', 'fillestare' => 'initial',
            'seanca e dyte' => 'second', 'shqyrtimi i dyte' => 'second',
            'shqyrtim gjyqesor' => 'main_trial', 'shqyrtimi gjyqesor' => 'main_trial', 'seanca kryesore' => 'main_trial',
            'fjala perfundimtare' => 'closing_arguments', 'fjalet perfundimtare' => 'closing_arguments',
            'shpallja e aktgjykimit' => 'judgment_announcement', 'shpallje aktgjykimi' => 'judgment_announcement',
            'seance ne apel' => 'appeal_session', 'seanca e apelit' => 'appeal_session',
            'seance pergatitore' => 'preparatory', 'seanca pergatitore' => 'preparatory',
        ],
        'venue' => [
            'salle gjyqi' => 'courtroom', 'salla e gjykimit' => 'courtroom', 'salle' => 'courtroom',
            'zyre' => 'office', 'zyra e gjyqtarit' => 'office', 'ne zyre' => 'office',
        ],
        'delay_cause' => [
            'gjyqtari' => 'panel', 'trupi gjykues' => 'panel', 'gjykata' => 'panel',
            'prokurori' => 'prosecutor', 'mbrojtja' => 'defence', 'mbrojtesi' => 'defence', 'avokati' => 'defence',
            'i akuzuari' => 'accused', 'deshmitari' => 'witness', 'tjeter' => 'other', 'te tjera' => 'other',
        ],
        'postponement_reason' => [
            'mungesa e prokurorit' => 'prosecutor', 'prokurori' => 'prosecutor',
            'mungesa e gjyqtarit' => 'judge', 'gjyqtari' => 'judge',
            'mungesa e mbrojtesit' => 'defence_lawyer', 'mbrojtesi' => 'defence_lawyer',
            'mungesa e te akuzuarit' => 'accused', 'mungesa e te pandehurit' => 'accused', 'i pandehuri' => 'accused',
            'mungesa e te demtuarit' => 'injured_party', 'pala e demtuar' => 'injured_party',
            'mungesa e ekspertit' => 'expert', 'eksperti' => 'expert',
            'arsye tjeter' => 'other', 'tjeter' => 'other',
        ],
        'recording' => [
            'jo' => 'none', 'pa incizim' => 'none', 'audio' => 'audio', 'zeri' => 'audio',
            'video' => 'video', 'audio video' => 'audio_video', 'audio dhe video' => 'audio_video',
        ],
        'participant' => [
            'gjyqtari' => 'judge', 'trupi gjykues' => 'panel', 'prokurori' => 'prosecutor', 'mbrojtesi' => 'defence_lawyer',
            'i akuzuari' => 'accused', 'i pandehuri' => 'accused', 'i demtuari' => 'injured_party', 'deshmitari' => 'witness',
            'eksperti' => 'expert', 'perkthyesi' => 'interpreter', 'publiku' => 'public', 'media' => 'media',
        ],
        'phone_user' => [
            'gjyqtari' => 'judge', 'prokurori' => 'prosecutor', 'mbrojtesi' => 'defence_lawyer', 'i akuzuari' => 'accused',
            'i pandehuri' => 'accused', 'i demtuari' => 'injured_party', 'publiku' => 'public',
        ],
        'stage_outcome' => [
            'e konfirmuar' => 'approved', 'konfirmohet' => 'approved', 'aprovohet' => 'approved', 'pranohet' => 'accepted',
            'e hedhur poshte' => 'dismissed', 'hidhet poshte' => 'dismissed', 'refuzohet' => 'rejected', 'refuzuar' => 'rejected',
            'denues' => 'convicting', 'denuese' => 'convicting', 'lirues' => 'acquitting', 'liruese' => 'acquitting', 'refuzues' => 'rejecting',
            'pjeserisht' => 'mixed', 'vertetohet' => 'confirmed', 'ndryshohet' => 'modified', 'anulohet' => 'annulled',
            'kthehet ne rigjykim' => 'returned_for_retrial', 'rigjykim' => 'returned_for_retrial',
            'po' => 'approved', 'jo' => 'rejected',
        ],
        // Per-stage spellings are checked before the general list above.
        'stage_outcome.plea_agreement' => [
            'po' => 'accepted', 'pranohet' => 'accepted', 'e pranuar' => 'accepted', 'aprovohet' => 'accepted',
            'jo' => 'rejected', 'refuzohet' => 'rejected', 'e refuzuar' => 'rejected',
        ],
        'appellant' => [
            'prokurori' => 'prosecutor', 'prokuroria' => 'prosecutor', 'mbrojtja' => 'defence', 'mbrojtesi' => 'defence',
            'i akuzuari' => 'accused', 'i pandehuri' => 'accused', 'i demtuari' => 'injured_party', 'te dyja palet' => 'both_parties',
        ],
        'claim_type' => ['prone' => 'property', 'pronesore' => 'property', 'pune' => 'labour', 'marredhenie pune' => 'labour', 'familjare' => 'family', 'kontrate' => 'contract', 'demshperblim' => 'damages', 'trashegimi' => 'inheritance'],
        'plaintiff_type' => ['person fizik' => 'natural_person', 'fizik' => 'natural_person', 'person juridik' => 'legal_person', 'juridik' => 'legal_person', 'ojq' => 'ngo', 'avokati i popullit' => 'ombudsperson'],
        'representation' => ['vete' => 'self', 'personalisht' => 'self', 'avokat' => 'lawyer', 'me avokat' => 'lawyer', 'ojq' => 'ngo', 'avokati i popullit' => 'ombudsperson', 'pa perfaqesim' => 'none'],
        'civil_outcome' => ['aprovohet' => 'granted', 'miratohet' => 'granted', 'refuzohet' => 'rejected', 'pjeserisht' => 'partially_granted', 'pajtim' => 'settled', 'ujdi' => 'settled', 'terhiqet' => 'withdrawn', 'ne proces' => 'pending'],
        'legal_remedy_type' => ['kunder vendimit' => 'against_decision', 'ankese' => 'against_decision', 'heshtja e administrates' => 'against_silence', 'heshtje' => 'against_silence', 'pa mjet' => 'none'],
        'second_instance_outcome' => ['aprovohet' => 'approved', 'refuzohet' => 'rejected', 'pjeserisht' => 'partially_approved', 'anulohet' => 'annulled', 'pa vendim' => 'no_decision'],
    ],
];
