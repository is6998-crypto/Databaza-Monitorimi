<?php

return [
    'tagline' => 'Independent monitoring of Kosovo courts',
    'meta_description' => 'Statistics and anonymized case information from the independent monitoring of court hearings in Kosovo: postponements, punctuality, transparency, outcomes and sentences.',
    'skip' => 'Skip to content',
    'language' => 'Language',
    'show_data' => 'Show data',
    'days' => 'days',
    'suppression_note' => 'To protect privacy, every number below :threshold is hidden and shown as “<:threshold”. Only data verified by our editors is published.',
    'nav' => [
        'label' => 'Main navigation',
        'menu' => 'Menu',
        'home' => 'Home',
        'cases' => 'Case search',
        'publications' => 'Reports & publications',
        'open_data' => 'Open data',
        'methodology' => 'Methodology',
        'about' => 'About',
        'data_protection' => 'Data protection',
    ],
    'footer' => [
        'about' => 'Court-monitoring programme: trained monitors attend hearings and record structured, verified observations.',
        'privacy' => 'No personal names are ever published. Numbers under :threshold are suppressed.',
    ],
    'home' => [
        'kicker' => 'Court monitoring in Kosovo',
        'title' => 'How do Kosovo courts handle criminal, civil, administrative and economic cases?',
        'intro' => 'Our monitors attend court hearings across Kosovo and record how they are run. Explore the verified results by year, court and type of case.',
        'cta_cases' => 'Search monitored cases',
        'cta_data' => 'Download open data',
        'statistics' => 'Statistics',
        'latest_reports' => 'Latest reports',
        'all_reports' => 'All reports',
    ],
    'filters' => [
        'year' => 'Year',
        'all_years' => 'All years',
        'court' => 'Court',
        'all_courts' => 'All courts',
        'type' => 'Type of case',
        'all_types' => 'All types',
        'category' => 'Offence category',
        'outcome' => 'Judgment',
        'all' => 'All',
        'reset' => 'Reset',
    ],
    'stats' => [
        'cases' => 'Monitored cases',
        'hearings' => 'Hearings monitored',
        'postponement_rate' => 'Hearings postponed',
        'late_rate' => 'Started over 15 min late',
        'public_rate' => 'Public hearings',
        'conviction_rate' => 'Convictions in judgments',
    ],
    'charts' => [
        'hearings_by_year' => 'Hearings monitored per year',
        'cases_by_type' => 'Monitored cases by type',
        'postponement_by_court' => 'Share of hearings postponed, by court (%)',
        'postponement_reasons' => 'Reasons for postponement',
        'verdicts' => 'First-instance judgments',
        'durations' => 'Median days between stages',
    ],
    'cases' => [
        'title' => 'Search monitored cases',
        'intro' => 'Anonymized information on verified monitored cases. No names of parties, judges or prosecutors and no case numbers are shown; dates are reduced to month and year.',
        'search' => 'Offence or article',
        'search_placeholder' => 'e.g. bribe or 414',
        'found' => '{0} No cases found|{1} :count case found|[2,*] :count cases found',
        'no_offence' => 'Offence not recorded',
        'stage' => 'Latest stage',
        'outcome' => 'Judgment',
        'hearings' => 'Hearings monitored',
        'postponed' => 'Postponed',
        'back' => 'Back to case search',
        'offences' => 'Offences',
        'timeline' => 'Timeline',
        'monitored_hearings' => 'Monitored hearings',
        'hearing_total' => 'Total',
        'hearing_postponed' => 'Postponed',
        'hearing_late' => 'Started late',
        'hearing_public' => 'Public',
        'monitored_between' => 'Monitored between :first and :last.',
        'anonymized_note' => 'This page is anonymized: it contains no personal names, no case numbers and no exact dates.',
    ],
    'publications' => [
        'intro' => 'Reports, policy briefs and datasets based on the monitoring findings.',
        'empty' => 'No publications yet.',
        'download' => 'Download the report (PDF)',
    ],
    'open_data' => [
        'intro' => 'Aggregate tables in CSV format, free to reuse. Every download is counted anonymously.',
        'download' => 'Download CSV',
        'licence' => 'You may reuse the data with attribution to the court-monitoring programme.',
        'datasets' => [
            'hearings-by-year-and-court' => 'Hearings and postponements by year and court',
            'postponement-by-court' => 'Postponement rate by court',
            'postponement-reasons' => 'Reasons for postponement',
            'cases-by-type' => 'Monitored cases by type',
            'verdicts' => 'First-instance judgments by outcome',
            'stage-durations' => 'Median days between case stages',
        ],
    ],
    'columns' => [
        'year' => 'Year',
        'court' => 'Court',
        'hearings' => 'Hearings',
        'postponed' => 'Postponed',
        'rate' => 'Rate (%)',
        'reason' => 'Reason',
        'count' => 'Count',
        'case_type' => 'Type of case',
        'cases' => 'Cases',
        'outcome' => 'Outcome',
        'stages' => 'Stages',
        'median_days' => 'Median days',
    ],
    'pages' => [
        'methodology' => [
            'title' => 'Methodology',
            'intro' => 'How hearings are selected, observed, recorded and verified before anything is published.',
            'sections' => [
                [
                    'heading' => 'Monitoring',
                    'paragraphs' => [
                        'Trained monitors attend hearings in the basic courts, the Court of Appeals and the Supreme Court. They follow cases from the criminal report to the final decision and fill in a structured questionnaire for every hearing they attend.',
                        'For each hearing they record, among other things, whether it started on time, whether it was postponed and why, whether it was public, recorded and interpreted when needed, and who was present.',
                    ],
                ],
                [
                    'heading' => 'Verification',
                    'paragraphs' => [
                        'Every case and every hearing goes through a review: the monitor submits it, an editor checks it and either approves it or returns it with a comment. Only approved records are included in statistics, the case search and open data.',
                    ],
                ],
                [
                    'heading' => 'Indicators',
                    'paragraphs' => [
                        'Rates are calculated over monitored hearings, not over all hearings held in Kosovo courts. Durations are medians of the number of days between two recorded stages of the same case.',
                    ],
                ],
                [
                    'heading' => 'Privacy protection',
                    'paragraphs' => [
                        'Statistics never show names. Every number below five is hidden, and percentages and medians based on fewer than five records are not shown, so that individuals cannot be identified.',
                    ],
                ],
            ],
        ],
        'about' => [
            'title' => 'About the programme',
            'intro' => 'An independent court-monitoring programme that promotes fair, efficient and transparent justice in Kosovo.',
            'sections' => [
                [
                    'heading' => 'What we do',
                    'paragraphs' => [
                        'We observe criminal, civil, administrative and economic cases, with a focus on corruption and organised crime, and publish verified findings, reports and recommendations.',
                    ],
                ],
                [
                    'heading' => 'Who can use the data',
                    'paragraphs' => [
                        'Everyone can explore the statistics and the anonymized case search. Researchers can apply for access to anonymized record-level data and an API in the researcher area, after accepting a data-use agreement.',
                    ],
                ],
            ],
        ],
        'data-protection' => [
            'title' => 'Data protection',
            'intro' => 'How personal data is protected, in line with Law No. 06/L-082 on Protection of Personal Data and, as a reference, the EU General Data Protection Regulation (GDPR).',
            'sections' => [
                [
                    'heading' => 'What we process',
                    'paragraphs' => [
                        'Monitors record observations about court hearings. Where needed for the monitoring, the internal database contains the names of parties; these are encrypted at rest and accessible only to authorised staff.',
                        'Public pages, open data, the researcher area and the API never contain personal names, case numbers or exact dates of individual cases.',
                    ],
                ],
                [
                    'heading' => 'Visitors of this website',
                    'paragraphs' => [
                        'This website does not use advertising or third-party tracking and does not load fonts or scripts from other services. Downloads of open data are counted without identifying the visitor.',
                    ],
                ],
                [
                    'heading' => 'Security',
                    'paragraphs' => [
                        'Staff accounts are protected with two-factor authentication, every change and export is recorded in an audit log, connections are encrypted and backups are encrypted and stored off-site.',
                    ],
                ],
                [
                    'heading' => 'Your rights',
                    'paragraphs' => [
                        'You can ask whether data about you is processed, and request correction or deletion where the law allows, by contacting the programme’s data protection officer. You can also complain to the Information and Privacy Agency.',
                    ],
                ],
            ],
        ],
    ],
];
