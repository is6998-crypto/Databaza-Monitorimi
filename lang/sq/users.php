<?php

return [
    'model' => 'Përdorues',
    'plural' => 'Përdoruesit',
    'sections' => [
        'account' => 'Llogaria',
        'roles' => 'Rolet dhe lejet',
        'monitor' => 'Profili i monitoruesit',
    ],
    'fields' => [
        'name' => 'Emri',
        'email' => 'Email',
        'password' => 'Fjalëkalimi',
        'locale' => 'Gjuha',
        'is_active' => 'Aktiv',
        'roles' => 'Rolet',
        'mfa' => '2FA',
        'last_login_at' => 'Kyçja e fundit',
        'monitor_code' => 'Kodi i monitoruesit',
        'monitor_phone' => 'Telefoni',
        'monitor_courts' => 'Gjykatat e caktuara',
    ],
    'help' => [
        'password_keep' => 'Lëreni bosh për të mbajtur fjalëkalimin aktual.',
        'roles' => 'Vetëm Super administratori mund t\'i ndryshojë rolet. Çdo ndryshim regjistrohet në regjistrin e auditimit.',
        'monitor' => 'Monitoruesit shohin vetëm lëndët në gjykatat e caktuara (dhe lëndët që i kanë krijuar ose u janë caktuar).',
    ],
    'actions' => [
        'reset_mfa' => 'Rivendos 2FA',
        'reset_mfa_confirm' => 'Përdoruesi do të duhet ta konfigurojë sërish vërtetimin me dy faktorë në kyçjen e ardhshme.',
        'reset_mfa_done' => 'Vërtetimi me dy faktorë u rivendos.',
    ],
];
