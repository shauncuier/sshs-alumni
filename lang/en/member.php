<?php

declare(strict_types=1);

return [

    'approval_required' => 'Your membership is still being reviewed. The directory and community open once the committee approves your application.',

    'nav' => [
        'dashboard' => 'Dashboard',
        'profile' => 'My Profile',
        'card' => 'Membership Card',
        'directory' => 'Directory',
        'community' => 'Community',
        'batch' => 'My Batch',
        'events' => 'My Events',
        'payments' => 'Payments',
        'donations' => 'Donations',
    ],

    'status' => [
        'pending' => 'Your application has been received and is waiting for review.',
        'under_review' => 'The committee is reviewing your application.',
        'approved' => 'Your membership is approved.',
        'rejected' => 'Your application was not approved.',
        'suspended' => 'Your membership is currently suspended.',
        'archived' => 'Your membership record has been archived.',
    ],

    'profile' => [
        'completion' => 'Profile :percent% complete',
        'missing' => 'Still to add: :groups',
    ],

    'photo' => [
        'title' => 'Profile photo',
        'help' => 'JPG, PNG or WebP, up to :size. A clear head-and-shoulders photo works best on the directory and your membership card.',
        'choose' => 'Choose a photo',
        'upload' => 'Upload photo',
        'none' => 'No photo yet',
    ],

    'batch' => [
        'title' => 'My Batch',
        'none' => 'You have not been assigned to a batch yet. Add your SSC year to your profile and the committee will place you.',
        'coordinators' => 'Batch coordinators',
        'no_coordinators' => 'No coordinator has been assigned to this batch yet.',
        'members' => 'Batch members',
        'hidden_note' => 'Members who have hidden themselves from the batch list are not shown here, but they are counted.',
    ],

    'directory' => [
        'count' => 'No members|:count member|:count members',
        'search_placeholder' => 'Search by name, organisation, city or membership number',
        'empty' => 'No member matches these filters. Try removing one.',
        'grid_view' => 'Grid view',
        'list_view' => 'List view',
    ],

];
