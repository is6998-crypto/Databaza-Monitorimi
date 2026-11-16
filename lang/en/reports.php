<?php

return [
    'model' => 'Hearing report',
    'plural' => 'Hearing reports',
    'title' => 'Report on the hearing of :date — :case',
    'subheading' => 'For every hearing the monitor writes what happened. The editor reads it, edits it and leaves comments.',
    'words' => '{0} No text|{1} :count word|[2,*] :count words',
    'empty_body' => 'The report has no text yet.',
    'sections' => [
        'hearing' => 'Hearing',
        'report' => 'Report',
    ],
    'fields' => [
        'hearing' => 'Hearing',
        'title' => 'Title',
        'body' => 'What happened at the hearing',
        'status' => 'Status',
        'author' => 'Written by',
        'reviewer' => 'Reviewed by',
        'comments' => 'Comments',
        'updated_at' => 'Updated',
        'submitted_at' => 'Sent',
    ],
    'help' => [
        'hearing' => 'Choose the hearing you are writing about. Search by case number. Only hearings without a report are listed.',
        'report' => 'Describe how the hearing went: who was present, what was said and decided, when the next hearing is. The text is stored encrypted and stays inside the panel.',
    ],
    'filters' => [
        'mine' => 'My reports',
    ],
    'actions' => [
        'write' => 'Write report',
        'write_for_hearing' => 'Write the report',
        'open_for_hearing' => 'Hearing report',
        'submit' => 'Send to the editor',
        'submit_help' => 'The editor is notified and can edit the report or leave comments. You can change it until it is approved.',
        'approve' => 'Approve',
        'approve_help' => 'The report is marked as finished and the monitor can no longer change it.',
        'send_back' => 'Send back with a comment',
        'send_back_help' => 'The monitor sees your comment, completes the report and sends it again.',
    ],
    'done' => [
        'submitted' => 'The report was sent to the editor.',
        'approved' => 'The report was approved.',
        'returned' => 'The report was sent back to the monitor.',
    ],
    'errors' => [
        'empty' => 'Write the report before sending it.',
        'comment_required' => 'Write a comment.',
        'exists' => 'This hearing already has a report.',
    ],
    'comments' => [
        'title' => 'Comments',
        'description' => 'The conversation between the editor and the monitor about this report.',
        'empty' => 'No comments yet.',
        'field' => 'Comment',
        'placeholder' => 'Write a comment…',
        'post' => 'Add comment',
        'author' => 'Monitor',
        'editor' => 'Editor',
        'deleted_user' => 'Deleted user',
    ],
    'empty' => [
        'heading' => 'No reports yet',
        'description' => 'After every hearing, write a short report on what happened.',
    ],
    'queue' => [
        'title' => 'Reports waiting for the editor',
        'empty' => 'No reports are waiting',
    ],
    'notifications' => [
        'submitted' => 'New report to review: hearing of :date (:case)',
        'returned' => 'Report sent back with a comment: hearing of :date (:case)',
        'approved' => 'Report approved: hearing of :date (:case)',
        'commented' => 'New comment on the report: hearing of :date (:case)',
    ],
];
