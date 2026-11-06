<?php

return [
    'courts' => [
        'singular' => 'Court',
        'plural' => 'Courts',
    ],
    'court_branches' => [
        'singular' => 'Court branch',
        'plural' => 'Court branches',
    ],
    'departments' => [
        'singular' => 'Department',
        'plural' => 'Departments',
    ],
    'divisions' => [
        'singular' => 'Division',
        'plural' => 'Divisions',
    ],
    'prosecution_offices' => [
        'singular' => 'Prosecution office',
        'plural' => 'Prosecution offices',
    ],
    'judges' => [
        'singular' => 'Judge',
        'plural' => 'Judges',
    ],
    'prosecutors' => [
        'singular' => 'Prosecutor',
        'plural' => 'Prosecutors',
    ],
    'monitors' => [
        'singular' => 'Monitor',
        'plural' => 'Monitors',
    ],
    'applicants' => [
        'singular' => 'Applicant (criminal report)',
        'plural' => 'Applicants',
    ],
    'criminal_codes' => [
        'singular' => 'Criminal code',
        'plural' => 'Criminal codes',
    ],
    'criminal_offences' => [
        'singular' => 'Criminal offence',
        'plural' => 'Criminal offences',
    ],
    'categories' => [
        'singular' => 'Offence category',
        'plural' => 'Offence categories',
    ],
    'nationalities' => [
        'singular' => 'Nationality',
        'plural' => 'Nationalities',
    ],
    'defendant_positions' => [
        'singular' => 'Position held',
        'plural' => 'Positions held',
    ],
    'economic_statuses' => [
        'singular' => 'Economic status',
        'plural' => 'Economic statuses',
    ],
    'security_measure_types' => [
        'singular' => 'Security measure type',
        'plural' => 'Security measure types',
    ],
    'fields' => [
        'name' => 'Name',
        'name_sq' => 'Name (Albanian)',
        'name_en' => 'Name (English)',
        'name_sr' => 'Name (Serbian)',
        'title_sq' => 'Title (Albanian)',
        'title_en' => 'Title (English)',
        'title_sr' => 'Title (Serbian)',
        'is_active' => 'Active',
        'updated_at' => 'Updated',
        'code' => 'Code',
        'level' => 'Level',
        'city' => 'City',
        'court' => 'Court',
        'department' => 'Department',
        'prosecution_office' => 'Prosecution office',
        'user' => 'User account',
        'phone' => 'Phone',
        'courts' => 'Assigned courts',
        'year' => 'Year',
        'criminal_code' => 'Criminal code',
        'article_number' => 'Article',
        'chapter' => 'Chapter',
        'category' => 'Category',
    ],
    'help' => [
        'serbian' => 'Optional — the interface is Serbian-ready.',
    ],
    'actions' => [
        'deactivate' => 'Deactivate selected',
    ],
    'merge' => [
        'action' => 'Merge duplicates',
        'heading' => 'Merge duplicate records',
        'description' => 'All cases, hearings and other records that use the selected duplicates will be re-pointed to the record you keep. The duplicates are then deactivated and moved to the bin.',
        'keep' => 'Record to keep',
        'submit' => 'Merge',
        'need_two' => 'Select at least two records to merge.',
        'done' => 'Merged :count duplicate(s) into ":name".',
        'repointed' => ':count linked record(s) were updated.',
    ],
    'import' => [
        'action' => 'Import CSV',
        'template' => 'CSV template',
        'file' => 'CSV file',
        'description' => 'First row must be a header. Columns: :columns. Existing rows are matched and updated; new rows are created.',
        'done' => 'Import finished: :created created, :updated updated, :skipped skipped.',
        'failed' => 'The file could not be imported.',
        'missing_columns' => 'Missing required columns: :columns',
        'row_error' => 'Line :line: :message',
        'unknown_reference' => 'Line :line: unknown :column ":value".',
    ],
];
