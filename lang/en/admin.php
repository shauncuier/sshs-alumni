<?php

declare(strict_types=1);

return [

    'nav' => [
        'dashboard' => 'Dashboard',
        'members' => 'Members',
        'verification' => 'Verification',
        'crm' => 'CRM',
        'batches' => 'Batches',
        'events' => 'Events',
        'jubilee' => 'Golden Jubilee',
        'payments' => 'Payments',
        'donations' => 'Donations',
        'sponsors' => 'Sponsors',
        'volunteers' => 'Volunteers',
        'committees' => 'Committees',
        'community' => 'Community',
        'news' => 'News',
        'announcements' => 'Announcements',
        'gallery' => 'Gallery',
        'pages' => 'Pages',
        'transitioned' => 'Status changed to :status.',
        'correction_sent' => 'The correction request was sent to the member.',
        'number_assigned' => 'Membership number :number assigned.',
        'correction_placeholder' => 'Tell the member what needs correcting.',
        'number_placeholder' => 'Leave blank to generate one',
        'no_permission' => 'You do not have permission to verify members.',
        'history' => 'School History',
        'reports' => 'Reports',
        'settings' => 'Settings',
        'users' => 'Users',
        'roles' => 'Roles & Permissions',
        'audit' => 'Audit Logs',
    ],

    'groups' => [
        'people' => 'People',
        'events' => 'Events',
        'money' => 'Finance',
        'content' => 'Content',
        'system' => 'System',
    ],

    'dashboard' => [
        'total_members' => 'Total members',
        'verified_members' => 'Verified members',
        'pending_registrations' => 'Pending registrations',
        'new_this_month' => 'New this month',
        'event_registrations' => 'Event registrations',
        'attendance' => 'Attendance',
        'donations' => 'Donations',
        'volunteers' => 'Volunteers',
    ],

    'verification' => [
        'approve' => 'Approve',
        'reject' => 'Reject',
        'suspend' => 'Suspend',
        'request_correction' => 'Request correction',
        'internal_note' => 'Internal note',
        'internal_note_hint' => 'Never shown to the member.',
        'history' => 'Verification history',
        'assign_number' => 'Assign membership number',
    ],

    'jubilee' => [
        'announce_date' => 'Announce the date',
        'date_not_set' => 'No date has been published yet. The public site shows "the date will be announced soon".',
        'date_published' => 'The date is published and visible on the public site.',
    ],

    'members' => [
        'search_placeholder' => 'Search by name, organisation or membership number',
        'empty' => 'No member matches these filters.',
    ],
    'batches' => [
        'search_placeholder' => 'Search by batch name or SSC year',
        'empty' => 'No batch matches this search.',
        'intro' => 'Batches are keyed by SSC year. Member counts are maintained automatically as members are approved and reassigned.',
        'create' => 'Add a batch',
        'ssc_year' => 'SSC year',
        'members_count' => 'Members',
        'coordinators' => 'Coordinators',
        'no_coordinators' => 'None yet',
        'coordinator_add' => 'Assign a coordinator',
        'coordinator_added' => 'Coordinator assigned.',
        'coordinator_removed' => 'Coordinator removed.',
        'coordinator_note' => 'Only approved members of this batch can coordinate it. A coordinator can edit this batch and see its members in the admin panel.',
        'no_candidates' => 'No approved member of this batch is available to assign.',
        'details' => 'Batch details',
    ],

    'roles' => [
        'intro' => 'Change what each role can do. Takes effect on the next request.',
        'locked' => 'Locked',
        'protected' => 'The :role role cannot be edited.',
        'protected_note' => 'This role is protected and cannot be changed. Super Admin bypasses every check, and removing it could lock the committee out permanently.',
        'updated' => 'Permissions for :role were updated.',
    ],

];
