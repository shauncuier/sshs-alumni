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

    'directory' => [
        'count' => ':count members',
        'search_placeholder' => 'Search by name, organisation, city or membership number',
        'empty' => 'No member matches these filters. Try removing one.',
        'grid_view' => 'Grid view',
        'list_view' => 'List view',
    ],

];
