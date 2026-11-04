<?php

return [
    'actions' => [
        'export' => 'Eksporto',
        'export_help' => 'Eksporton të gjithë regjistrat që përputhen me filtrat dhe kërkimin aktual. Eksporti regjistrohet. Emrat e palëve nuk përfshihen kurrë.',
        'export_selected' => 'Eksporto të zgjedhurat',
        'assign_monitor' => 'Cakto monitoruesin',
        'change_status' => 'Ndrysho statusin',
    ],
    'fields' => [
        'format' => 'Formati',
    ],
    'done' => [
        'assigned' => 'Monitoruesi u caktua në :count lëndë.',
        'status' => 'Statusi u ndryshua për :done regjistra; :skipped u anashkaluan (nuk lejohet në statusin aktual).',
    ],
    'columns' => [
        'parties' => 'Palët',
        'accused' => 'Të akuzuarit',
        'latest_stage_date' => 'Data e fazës së fundit',
        'hearing_ref' => 'Referenca e seancës',
    ],
    'log' => [
        'model' => 'Eksport',
        'plural' => 'Regjistri i eksporteve',
        'context' => 'Ku',
        'resource' => 'Të dhënat',
        'rows' => 'Rreshta',
        'anonymous' => 'Vizitor anonim',
    ],
    'contexts' => [
        'admin' => 'Paneli i stafit',
        'researcher' => 'Zona e hulumtuesve',
        'api' => 'API',
        'public' => 'Të dhëna të hapura',
    ],
];
