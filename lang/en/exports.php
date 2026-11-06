<?php

return [
    'actions' => [
        'export' => 'Export',
        'export_help' => 'Exports every record matching the current filters and search. The export is logged. Party names are never included.',
        'export_selected' => 'Export selected',
        'assign_monitor' => 'Assign monitor',
        'change_status' => 'Change status',
    ],
    'fields' => [
        'format' => 'Format',
    ],
    'done' => [
        'assigned' => 'Monitor assigned to :count case(s).',
        'status' => 'Status changed for :done record(s); :skipped skipped (not allowed in their current status).',
    ],
    'columns' => [
        'parties' => 'Parties',
        'accused' => 'Accused',
        'latest_stage_date' => 'Date of latest stage',
        'hearing_ref' => 'Hearing reference',
    ],
    'log' => [
        'model' => 'Export',
        'plural' => 'Export log',
        'context' => 'Where',
        'resource' => 'Data',
        'rows' => 'Rows',
        'anonymous' => 'Anonymous visitor',
    ],
    'contexts' => [
        'admin' => 'Staff panel',
        'researcher' => 'Researcher area',
        'api' => 'API',
        'public' => 'Open data',
    ],
];
