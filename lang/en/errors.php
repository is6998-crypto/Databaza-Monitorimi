<?php

return [
    'home' => 'Go to the home page',
    'back' => 'Go back',
    '401' => ['title' => 'Please sign in', 'message' => 'You need to sign in to see this page.'],
    '403' => ['title' => 'Access denied', 'message' => 'You do not have permission to see this page.'],
    '404' => ['title' => 'Page not found', 'message' => 'The page you are looking for does not exist or was moved.'],
    '419' => ['title' => 'Session expired', 'message' => 'Your session has expired. Reload the page and try again.'],
    '429' => ['title' => 'Too many requests', 'message' => 'You sent too many requests. Wait a moment and try again.'],
    '500' => ['title' => 'Something went wrong', 'message' => 'The problem has been logged. Please try again later.'],
    '503' => ['title' => 'Under maintenance', 'message' => 'The platform is being updated. Please try again in a few minutes.'],
    'alert' => [
        'subject' => 'Error on the platform: :error',
        'intro' => 'The platform ran into an error. The details below contain no personal data; the same error is reported at most once an hour.',
        'outro' => 'See the application log (storage/logs) for the full details.',
        'fields' => [
            'error' => 'Error',
            'message' => 'Message',
            'where' => 'Where in the code',
            'page' => 'Page',
            'user' => 'User',
            'environment' => 'Environment',
            'time' => 'Time',
        ],
    ],
];
