<?php

return [
    'history' => 'Review history',
    'from' => 'From',
    'to' => 'To',
    'comment' => 'Comment',
    'no_history' => 'No status changes yet',
    'errors' => [
        'invalid' => 'A record that is ":from" cannot be moved to ":to".',
        'comment_required' => 'Write a comment explaining what needs to be corrected.',
    ],
    'items' => [
        'case' => 'case :case',
        'hearing' => 'hearing of :date (:case)',
    ],
    'notifications' => [
        'submitted' => 'New submission to review: :item',
        'returned' => 'Returned for correction: :item',
        'approved' => 'Approved: :item',
        'published' => 'Published: :item',
        'changed' => 'Status changed: :item',
        'by' => 'By :name',
    ],
];
