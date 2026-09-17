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

    'events' => [
        'title' => 'My Events',
        'empty' => 'You have not registered for anything yet.',
        'upcoming' => 'Upcoming',
        'past' => 'Past',
        'register' => 'Register',
        'registered' => 'You are registered. Your pass is below.',
        'waitlisted' => 'The event is full, so you are on the waitlist. The committee will be in touch.',
        'already_registered' => 'You are already registered for this event.',
        'closed' => 'Registration for this event is not open.',
        'cancelled' => 'Your registration has been cancelled.',
        'cancel' => 'Cancel registration',
        'cancel_confirm' => 'Cancel your registration? Your seat goes back to the pool and someone on the waitlist may take it.',
        'guests' => 'Guests',
        'guest_name' => 'guest name',
        'add_guest' => 'Add a guest',
        'remove_guest' => 'Remove',
        'guest_relation' => 'Relation',
        'guest_age_group' => 'Age group',
        'seats' => 'No seats|:count seat|:count seats',
        'notes' => 'Anything the organisers should know',
        'ticket_type' => 'Ticket',
        'amount_due' => 'Amount due',
        'pass' => 'Your pass',
        'pass_help' => 'Show this at the gate. The organisers scan it; there is nothing to print.',
        'checked_in' => 'Checked in at :time',
        'print' => 'Print',
    ],

    'card' => [
        'title' => 'Membership Card',
        'help' => 'Anyone can scan this to confirm your membership. It shows your name, batch and status, and nothing else.',
        'verify_hint' => 'Scan to verify',
        'print' => 'Print card',
        'not_approved' => 'Your card is issued once the committee approves your membership.',
    ],

    'directory' => [
        'count' => 'No members|:count member|:count members',
        'search_placeholder' => 'Search by name, organisation, city or membership number',
        'empty' => 'No member matches these filters. Try removing one.',
        'grid_view' => 'Grid view',
        'list_view' => 'List view',
    ],

];
