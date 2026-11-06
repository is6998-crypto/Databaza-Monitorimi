<?php

return [
    'model' => 'User',
    'plural' => 'Users',
    'sections' => [
        'account' => 'Account',
        'roles' => 'Roles and permissions',
        'monitor' => 'Monitor profile',
    ],
    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'locale' => 'Language',
        'is_active' => 'Active',
        'roles' => 'Roles',
        'mfa' => '2FA',
        'last_login_at' => 'Last login',
        'monitor_code' => 'Monitor code',
        'monitor_phone' => 'Phone',
        'monitor_courts' => 'Assigned courts',
    ],
    'help' => [
        'password_keep' => 'Leave empty to keep the current password.',
        'roles' => 'Only a Super Admin can change roles. Every change is recorded in the audit log.',
        'monitor' => 'Monitors only see cases in their assigned courts (plus cases they created or are assigned to).',
    ],
    'actions' => [
        'reset_mfa' => 'Reset 2FA',
        'reset_mfa_confirm' => 'The user will have to set up two-factor authentication again at the next login.',
        'reset_mfa_done' => 'Two-factor authentication was reset.',
    ],
];
