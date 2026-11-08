<?php

// Albanian for strings Filament's own Albanian translation lacks (merged over the package's file).

return [
    'column_manager' => [
        'position' => 'Pozicioni :position nga :count.',
        'position_in_group' => 'Pozicioni :position nga :count në :group.',
        'reordered' => ':column u zhvendos në pozicionin :position nga :count.',
        'reordered_deferred' => ':column u zhvendos në pozicionin :position nga :count. Zbatoni kolonat për t\'i ruajtur ndryshimet.',
        'reordered_in_group' => ':column u zhvendos në pozicionin :position nga :count në :group.',
        'reordered_in_group_deferred' => ':column u zhvendos në pozicionin :position nga :count në :group. Zbatoni kolonat për t\'i ruajtur ndryshimet.',
        'actions' => [
            'apply' => [
                'label' => 'Zbato kolonat',
            ],
            'move_up' => [
                'label' => 'Lëviz :column lart',
            ],
            'move_down' => [
                'label' => 'Lëviz :column poshtë',
            ],
            'reorder' => [
                'label' => 'Rirendit kolonën',
            ],
            'reset' => [
                'label' => 'Rivendos',
            ],
        ],
    ],
    'columns' => [
        'actions' => [
            'label' => 'Veprimi|Veprimet',
        ],
        'editable' => [
            'saving' => 'Duke ruajtur ndryshimin...',
            'saved' => 'Ndryshimi u ruajt.',
            'failure' => 'Ruajtja nuk u konfirmua. Rifreskoni faqen para se të provoni përsëri.',
            'unavailable' => 'Ky element nuk mund të ndryshohet më.',
        ],
        'icon' => [
            'boolean' => [
                'true' => 'Po',
                'false' => 'Jo',
            ],
        ],
        'select' => [
            'loading_message' => 'Duke u ngarkuar...',
            'no_options_message' => 'Nuk ka opsione.',
            'no_search_results_message' => 'Asnjë opsion nuk përputhet me kërkimin.',
            'placeholder' => 'Zgjidhni një opsion',
            'searching_message' => 'Duke kërkuar...',
            'search_prompt' => 'Filloni të shkruani për të kërkuar',
        ],
        'text' => [
            'actions' => [
                'collapse_list' => 'Shfaq :count më pak',
                'expand_list' => 'Shfaq :count më shumë',
            ],
        ],
    ],
    'fields' => [
        'bulk_select_group' => [
            'label' => 'Zgjidh/hiq grupin :title për veprime në grup.',
        ],
    ],
    'actions' => [
        'reorder_record' => [
            'label' => 'Rirendit elementin :key',
        ],
        'move_record_up' => [
            'label' => 'Lëviz :label lart',
        ],
        'move_record_down' => [
            'label' => 'Lëviz :label poshtë',
        ],
        'toggle_record_content' => [
            'label' => 'Hap/mbyll elementin :key',
        ],
    ],
    'filters' => [
        'actions' => [
            'apply' => [
                'label' => 'Zbato filtrat',
            ],
        ],
        'select' => [
            'relationship' => [
                'empty_option_label' => 'Asnjë',
            ],
        ],
    ],
    'loading' => 'Duke u ngarkuar...',
    'reordering' => [
        'position' => ':label, pozicioni :position nga :count.',
        'moved' => ':label u zhvendos në pozicionin :position nga :count.',
    ],
    'result_count' => '{0} Asnjë rezultat|{1} :count rezultat|[2,*] :count rezultate',
    'default_model_label' => 'regjistrim',
];
