<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Enum labels
|--------------------------------------------------------------------------
|
| Every backed enum resolves its display label through here, keyed by the
| snake_case class name. An enum never renders its raw backing value.
|
| Keys must match lang/bn/enums.php exactly — a parity test enforces it.
|
| @see App\Enums\Concerns\HasLabel
*/

return [

    'locale' => [
        'bn' => 'Bangla',
        'en' => 'English',
    ],

    'user_status' => [
        'active' => 'Active',
        'suspended' => 'Suspended',
        'disabled' => 'Disabled',
    ],

    'member_status' => [
        'pending' => 'Pending',
        'under_review' => 'Under review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'suspended' => 'Suspended',
        'archived' => 'Archived',
    ],

    'relation_type' => [
        'former_student' => 'Former student',
        'former_teacher' => 'Former teacher',
        'current_teacher' => 'Current teacher',
        'staff' => 'Staff',
        'guardian' => 'Guardian',
        'supporter' => 'Supporter',
        'other' => 'Other',
    ],

    'gender' => [
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other',
    ],

    'blood_group' => [
        'A+' => 'A+',
        'A-' => 'A−',
        'B+' => 'B+',
        'B-' => 'B−',
        'AB+' => 'AB+',
        'AB-' => 'AB−',
        'O+' => 'O+',
        'O-' => 'O−',
    ],

    'member_link_type' => [
        'linkedin' => 'LinkedIn',
        'facebook' => 'Facebook',
        'website' => 'Website',
        'x' => 'X',
        'youtube' => 'YouTube',
        'other' => 'Other',
    ],

    'batch_status' => [
        'active' => 'Active',
        'archived' => 'Archived',
    ],

    'event_type' => [
        'reunion' => 'Reunion',
        'seminar' => 'Seminar',
        'sports' => 'Sports',
        'cultural' => 'Cultural',
        'fundraising' => 'Fundraising',
        'meeting' => 'Meeting',
        'volunteer' => 'Volunteer',
        'general' => 'General',
    ],

    'event_status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'registration_open' => 'Registration open',
        'registration_closed' => 'Registration closed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'event_date_status' => [
        'tba' => 'To be announced',
        'announced' => 'Announced',
    ],

    'registration_status' => [
        'confirmed' => 'Confirmed',
        'waitlisted' => 'Waitlisted',
        'cancelled' => 'Cancelled',
    ],

    'crm_contact_type' => [
        'prospect' => 'Prospect',
        'volunteer' => 'Volunteer',
        'donor' => 'Donor',
        'sponsor' => 'Sponsor',
        'guest' => 'Guest',
        'partner' => 'Partner',
        'organization' => 'Organisation',
        'other' => 'Other',
    ],

    'pipeline_stage' => [
        'new' => 'New',
        'contacted' => 'Contacted',
        'interested' => 'Interested',
        'registered' => 'Registered',
        'verified' => 'Verified',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'archived' => 'Archived',
    ],

    'crm_activity_type' => [
        'note' => 'Note',
        'call' => 'Call',
        'email' => 'Email',
        'meeting' => 'Meeting',
        'task' => 'Task',
        'system' => 'System',
    ],

    'task_status' => [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'done' => 'Done',
        'cancelled' => 'Cancelled',
    ],

    'task_priority' => [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    'payment_status' => [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ],

    'payment_gateway_name' => [
        'manual' => 'Offline / manual',
    ],

    'fee_status' => [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'waived' => 'Waived',
        'cancelled' => 'Cancelled',
    ],

    'donation_status' => [
        'pending' => 'Pending',
        'received' => 'Received',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ],

    'sponsor_tier' => [
        'title' => 'Title Sponsor',
        'platinum' => 'Platinum',
        'gold' => 'Gold',
        'silver' => 'Silver',
        'partner' => 'Partner',
        'custom' => 'Custom',
    ],

    'sponsor_kind' => [
        'individual' => 'Individual',
        'company' => 'Company',
    ],

    'sponsor_status' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
    ],

    'volunteer_status' => [
        'applied' => 'Applied',
        'approved' => 'Approved',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'rejected' => 'Rejected',
    ],

    'assignment_status' => [
        'assigned' => 'Assigned',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'committee_type' => [
        'executive' => 'Executive',
        'organizing' => 'Organising',
        'event' => 'Event',
        'finance' => 'Finance',
        'media' => 'Media',
        'volunteer' => 'Volunteer',
        'batch' => 'Batch',
    ],

    'committee_member_status' => [
        'active' => 'Active',
        'past' => 'Past',
    ],

    'post_category' => [
        'general' => 'General',
        'reunion' => 'Alumni Reunion',
        'batch' => 'Batch Discussion',
        'memories' => 'School Memories',
        'career' => 'Career',
        'business' => 'Business',
        'support' => 'Community Support',
        'volunteer' => 'Volunteer Activities',
        'jubilee' => 'Golden Jubilee',
    ],

    'post_status' => [
        'published' => 'Published',
        'hidden' => 'Hidden',
        'removed' => 'Removed',
    ],

    'comment_status' => [
        'published' => 'Published',
        'hidden' => 'Hidden',
        'removed' => 'Removed',
    ],

    'reaction_type' => [
        'like' => 'Like',
        'love' => 'Love',
        'celebrate' => 'Celebrate',
        'support' => 'Support',
    ],

    'report_reason' => [
        'spam' => 'Spam',
        'abuse' => 'Abuse',
        'false_info' => 'False information',
        'harassment' => 'Harassment',
        'other' => 'Other',
    ],

    'report_status' => [
        'open' => 'Open',
        'reviewing' => 'Reviewing',
        'resolved' => 'Resolved',
        'dismissed' => 'Dismissed',
    ],

    'content_status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ],

    'story_status' => [
        'pending' => 'Pending review',
        'published' => 'Published',
        'rejected' => 'Rejected',
    ],

    'announcement_kind' => [
        'announcement' => 'Announcement',
        'notice' => 'Notice',
    ],

    'announcement_level' => [
        'info' => 'Information',
        'important' => 'Important',
        'urgent' => 'Urgent',
    ],

    'audience_scope' => [
        'public' => 'Public',
        'members' => 'Members',
        'batch' => 'Batch',
        'role' => 'Role',
    ],

    'faq_group' => [
        'general' => 'General',
        'jubilee' => 'Golden Jubilee',
        'membership' => 'Membership',
        'payment' => 'Payment',
        'event' => 'Event',
    ],

    'media_collection' => [
        'profile' => 'Profile photo',
        'gallery' => 'Gallery',
        'cover' => 'Cover',
        'banner' => 'Banner',
        'logo' => 'Logo',
        'document' => 'Document',
        'attachment' => 'Attachment',
    ],

    'campaign_channel' => [
        'mail' => 'Email',
        'sms' => 'SMS',
        'whatsapp' => 'WhatsApp',
        'database' => 'In-app',
    ],

    'campaign_status' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'sending' => 'Sending',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ],

    'campaign_recipient_status' => [
        'queued' => 'Queued',
        'sent' => 'Sent',
        'delivered' => 'Delivered',
        'failed' => 'Failed',
        'opened' => 'Opened',
        'bounced' => 'Bounced',
        'unsubscribed' => 'Unsubscribed',
    ],

    'audience_type' => [
        'all_members' => 'All members',
        'batch' => 'By batch',
        'status' => 'By status',
        'role' => 'By role',
        'custom' => 'Custom filter',
    ],

    'setting_group' => [
        'organization' => 'Association',
        'school' => 'School',
        'contact' => 'Contact',
        'social' => 'Social links',
        'registration' => 'Registration',
        'membership' => 'Membership',
        'event' => 'Events',
        'jubilee' => 'Golden Jubilee',
        'notification' => 'Notifications',
        'seo' => 'SEO',
        'privacy' => 'Privacy',
        'system' => 'System',
    ],

];
