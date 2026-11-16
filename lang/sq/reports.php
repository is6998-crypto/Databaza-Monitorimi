<?php

return [
    'model' => 'Raport i seancës',
    'plural' => 'Raportet e seancave',
    'title' => 'Raporti i seancës së :date — :case',
    'subheading' => 'Për çdo seancë monitoruesi shkruan çfarë ndodhi. Redaktori e lexon, e redakton dhe lë komente.',
    'words' => '{0} Pa tekst|{1} :count fjalë|[2,*] :count fjalë',
    'empty_body' => 'Raporti ende nuk ka tekst.',
    'sections' => [
        'hearing' => 'Seanca',
        'report' => 'Raporti',
    ],
    'fields' => [
        'hearing' => 'Seanca',
        'title' => 'Titulli',
        'body' => 'Çfarë ndodhi në seancë',
        'status' => 'Statusi',
        'author' => 'Shkruar nga',
        'reviewer' => 'Shqyrtuar nga',
        'comments' => 'Komente',
        'updated_at' => 'Përditësuar',
        'submitted_at' => 'Dërguar',
    ],
    'help' => [
        'hearing' => 'Zgjidhni seancën për të cilën shkruani. Kërkoni me numrin e lëndës. Shfaqen vetëm seancat që ende nuk kanë raport.',
        'report' => 'Përshkruani rrjedhën e seancës: kush ishte i pranishëm, çfarë u tha dhe u vendos, kur është seanca e radhës. Teksti ruhet i enkriptuar dhe mbetet brenda panelit.',
    ],
    'filters' => [
        'mine' => 'Raportet e mia',
    ],
    'actions' => [
        'write' => 'Shkruaj raport',
        'write_for_hearing' => 'Shkruaj raportin',
        'open_for_hearing' => 'Raporti i seancës',
        'submit' => 'Dërgo te redaktori',
        'submit_help' => 'Redaktori njoftohet dhe mund ta redaktojë raportin ose të lërë komente. Ju mund ta ndryshoni derisa të miratohet.',
        'approve' => 'Mirato',
        'approve_help' => 'Raporti shënohet si i përfunduar dhe monitoruesi nuk mund ta ndryshojë më.',
        'send_back' => 'Kthe me koment',
        'send_back_help' => 'Monitoruesi e sheh komentin tuaj, e plotëson raportin dhe e dërgon sërish.',
    ],
    'done' => [
        'submitted' => 'Raporti u dërgua te redaktori.',
        'approved' => 'Raporti u miratua.',
        'returned' => 'Raporti u kthye te monitoruesi.',
    ],
    'errors' => [
        'empty' => 'Shkruani raportin para se ta dërgoni.',
        'comment_required' => 'Shkruani një koment.',
        'exists' => 'Kjo seancë ka tashmë një raport.',
    ],
    'comments' => [
        'title' => 'Komentet',
        'description' => 'Biseda mes redaktorit dhe monitoruesit për këtë raport.',
        'empty' => 'Ende nuk ka komente.',
        'field' => 'Komenti',
        'placeholder' => 'Shkruani një koment…',
        'post' => 'Shto koment',
        'author' => 'Monitoruesi',
        'editor' => 'Redaktori',
        'deleted_user' => 'Përdorues i fshirë',
    ],
    'empty' => [
        'heading' => 'Ende nuk ka raporte',
        'description' => 'Pas çdo seance, shkruani një raport të shkurtër për atë që ndodhi.',
    ],
    'queue' => [
        'title' => 'Raportet në pritje të redaktorit',
        'empty' => 'Asnjë raport në pritje',
    ],
    'notifications' => [
        'submitted' => 'Raport i ri për shqyrtim: seanca e :date (:case)',
        'returned' => 'Raporti u kthye me koment: seanca e :date (:case)',
        'approved' => 'Raporti u miratua: seanca e :date (:case)',
        'commented' => 'Koment i ri te raporti: seanca e :date (:case)',
    ],
];
