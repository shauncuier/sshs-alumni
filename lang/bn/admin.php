<?php

declare(strict_types=1);

return [

    'nav' => [
        'dashboard' => 'ড্যাশবোর্ড',
        'members' => 'সদস্য',
        'verification' => 'যাচাই',
        'crm' => 'সিআরএম',
        'batches' => 'ব্যাচ',
        'events' => 'অনুষ্ঠান',
        'jubilee' => 'সুবর্ণজয়ন্তী',
        'payments' => 'পেমেন্ট',
        'donations' => 'অনুদান',
        'sponsors' => 'পৃষ্ঠপোষক',
        'volunteers' => 'স্বেচ্ছাসেবক',
        'committees' => 'কমিটি',
        'community' => 'কমিউনিটি',
        'news' => 'সংবাদ',
        'announcements' => 'বিজ্ঞপ্তি',
        'gallery' => 'ছবিঘর',
        'pages' => 'পেজ',
        'transitioned' => 'অবস্থা :status-এ পরিবর্তন করা হয়েছে।',
        'correction_sent' => 'সংশোধনের অনুরোধ সদস্যকে পাঠানো হয়েছে।',
        'number_assigned' => 'সদস্য নম্বর :number প্রদান করা হয়েছে।',
        'correction_placeholder' => 'কী সংশোধন করতে হবে সদস্যকে জানান।',
        'number_placeholder' => 'খালি রাখলে স্বয়ংক্রিয়ভাবে তৈরি হবে',
        'no_permission' => 'সদস্য যাচাই করার অনুমতি আপনার নেই।',
        'history' => 'বিদ্যালয়ের ইতিহাস',
        'reports' => 'রিপোর্ট',
        'settings' => 'সেটিংস',
        'users' => 'ব্যবহারকারী',
        'roles' => 'ভূমিকা ও অনুমতি',
        'audit' => 'অডিট লগ',
    ],

    'groups' => [
        'people' => 'সদস্য ও যোগাযোগ',
        'events' => 'অনুষ্ঠান',
        'money' => 'অর্থ',
        'content' => 'কনটেন্ট',
        'system' => 'সিস্টেম',
    ],

    'dashboard' => [
        'total_members' => 'মোট সদস্য',
        'verified_members' => 'যাচাইকৃত সদস্য',
        'pending_registrations' => 'অপেক্ষমাণ নিবন্ধন',
        'new_this_month' => 'এ মাসে নতুন',
        'event_registrations' => 'অনুষ্ঠানে নিবন্ধন',
        'attendance' => 'উপস্থিতি',
        'donations' => 'অনুদান',
        'volunteers' => 'স্বেচ্ছাসেবক',
    ],

    'verification' => [
        'approve' => 'অনুমোদন',
        'reject' => 'প্রত্যাখ্যান',
        'suspend' => 'স্থগিত',
        'request_correction' => 'সংশোধনের অনুরোধ',
        'internal_note' => 'অভ্যন্তরীণ নোট',
        'internal_note_hint' => 'সদস্যকে কখনও দেখানো হয় না।',
        'history' => 'যাচাইয়ের ইতিহাস',
        'assign_number' => 'সদস্য নম্বর প্রদান',
    ],

    'jubilee' => [
        'announce_date' => 'তারিখ ঘোষণা করুন',
        'date_not_set' => 'এখনও কোনো তারিখ প্রকাশ করা হয়নি। পাবলিক সাইটে দেখাচ্ছে "তারিখ শীঘ্রই ঘোষণা করা হবে"।',
        'date_published' => 'তারিখ প্রকাশিত এবং পাবলিক সাইটে দৃশ্যমান।',
    ],

    'members' => [
        'search_placeholder' => 'নাম, প্রতিষ্ঠান বা সদস্য নম্বর দিয়ে খুঁজুন',
        'empty' => 'এই ফিল্টারে কোনো সদস্য পাওয়া যায়নি।',
    ],
    'roles' => [
        'intro' => 'প্রতিটি ভূমিকা কী করতে পারবে তা পরিবর্তন করুন। পরবর্তী অনুরোধ থেকে কার্যকর হবে।',
        'locked' => 'সুরক্ষিত',
        'protected' => ':role ভূমিকা সম্পাদনা করা যায় না।',
        'protected_note' => 'এই ভূমিকা সুরক্ষিত, পরিবর্তন করা যায় না। Super Admin সব যাচাই এড়িয়ে যায়, এবং এটি সরালে কমিটি স্থায়ীভাবে প্রবেশাধিকার হারাতে পারে।',
        'updated' => ':role ভূমিকার অনুমতি হালনাগাদ হয়েছে।',
    ],

];
