<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Enum labels — বাংলা
|--------------------------------------------------------------------------
|
| Keys must match lang/en/enums.php exactly; a parity test enforces it.
|
| Blood groups stay in Latin script deliberately: they are written that way on
| every Bangladeshi donor card and hospital form, so translating them would
| make the field harder to use, not easier.
*/

return [

    'locale' => [
        'bn' => 'বাংলা',
        'en' => 'ইংরেজি',
    ],

    'user_status' => [
        'active' => 'সক্রিয়',
        'suspended' => 'স্থগিত',
        'disabled' => 'নিষ্ক্রিয়',
    ],

    'member_status' => [
        'pending' => 'অপেক্ষমাণ',
        'under_review' => 'পর্যালোচনাধীন',
        'approved' => 'অনুমোদিত',
        'rejected' => 'প্রত্যাখ্যাত',
        'suspended' => 'স্থগিত',
        'archived' => 'সংরক্ষিত',
    ],

    'relation_type' => [
        'former_student' => 'প্রাক্তন শিক্ষার্থী',
        'former_teacher' => 'প্রাক্তন শিক্ষক',
        'current_teacher' => 'বর্তমান শিক্ষক',
        'staff' => 'কর্মচারী',
        'guardian' => 'অভিভাবক',
        'supporter' => 'শুভানুধ্যায়ী',
        'other' => 'অন্যান্য',
    ],

    'gender' => [
        'male' => 'পুরুষ',
        'female' => 'নারী',
        'other' => 'অন্যান্য',
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
        'linkedin' => 'লিংকডইন',
        'facebook' => 'ফেসবুক',
        'website' => 'ওয়েবসাইট',
        'x' => 'এক্স',
        'youtube' => 'ইউটিউব',
        'other' => 'অন্যান্য',
    ],

    'batch_status' => [
        'active' => 'সক্রিয়',
        'archived' => 'সংরক্ষিত',
    ],

    'event_type' => [
        'reunion' => 'পুনর্মিলনী',
        'seminar' => 'সেমিনার',
        'sports' => 'ক্রীড়া',
        'cultural' => 'সাংস্কৃতিক',
        'fundraising' => 'তহবিল সংগ্রহ',
        'meeting' => 'সভা',
        'volunteer' => 'স্বেচ্ছাসেবা',
        'general' => 'সাধারণ',
    ],

    'event_status' => [
        'draft' => 'খসড়া',
        'published' => 'প্রকাশিত',
        'registration_open' => 'নিবন্ধন চলছে',
        'registration_closed' => 'নিবন্ধন বন্ধ',
        'completed' => 'সম্পন্ন',
        'cancelled' => 'বাতিল',
    ],

    'event_date_status' => [
        'tba' => 'শীঘ্রই ঘোষণা করা হবে',
        'announced' => 'ঘোষিত',
    ],

    'registration_status' => [
        'confirmed' => 'নিশ্চিত',
        'waitlisted' => 'অপেক্ষমাণ তালিকায়',
        'cancelled' => 'বাতিল',
    ],

    'crm_contact_type' => [
        'prospect' => 'সম্ভাব্য',
        'volunteer' => 'স্বেচ্ছাসেবক',
        'donor' => 'দাতা',
        'sponsor' => 'পৃষ্ঠপোষক',
        'guest' => 'অতিথি',
        'partner' => 'অংশীদার',
        'organization' => 'প্রতিষ্ঠান',
        'other' => 'অন্যান্য',
    ],

    'pipeline_stage' => [
        'new' => 'নতুন',
        'contacted' => 'যোগাযোগ হয়েছে',
        'interested' => 'আগ্রহী',
        'registered' => 'নিবন্ধিত',
        'verified' => 'যাচাইকৃত',
        'active' => 'সক্রিয়',
        'inactive' => 'নিষ্ক্রিয়',
        'archived' => 'সংরক্ষিত',
    ],

    'crm_activity_type' => [
        'note' => 'নোট',
        'call' => 'কল',
        'email' => 'ইমেইল',
        'meeting' => 'সভা',
        'task' => 'কাজ',
        'system' => 'সিস্টেম',
    ],

    'task_status' => [
        'open' => 'চলমান',
        'in_progress' => 'কাজ চলছে',
        'done' => 'সম্পন্ন',
        'cancelled' => 'বাতিল',
    ],

    'task_priority' => [
        'low' => 'কম',
        'normal' => 'স্বাভাবিক',
        'high' => 'বেশি',
        'urgent' => 'জরুরি',
    ],

    'payment_status' => [
        'pending' => 'অপেক্ষমাণ',
        'paid' => 'পরিশোধিত',
        'failed' => 'ব্যর্থ',
        'cancelled' => 'বাতিল',
        'refunded' => 'ফেরত দেওয়া হয়েছে',
    ],

    'payment_gateway_name' => [
        'manual' => 'সরাসরি / ম্যানুয়াল',
    ],

    'fee_status' => [
        'pending' => 'অপেক্ষমাণ',
        'paid' => 'পরিশোধিত',
        'waived' => 'মওকুফ',
        'cancelled' => 'বাতিল',
    ],

    'donation_status' => [
        'pending' => 'অপেক্ষমাণ',
        'received' => 'গৃহীত',
        'cancelled' => 'বাতিল',
        'refunded' => 'ফেরত দেওয়া হয়েছে',
    ],

    'sponsor_tier' => [
        'title' => 'প্রধান পৃষ্ঠপোষক',
        'platinum' => 'প্ল্যাটিনাম',
        'gold' => 'গোল্ড',
        'silver' => 'সিলভার',
        'partner' => 'পার্টনার',
        'custom' => 'কাস্টম',
    ],

    'sponsor_kind' => [
        'individual' => 'ব্যক্তি',
        'company' => 'প্রতিষ্ঠান',
    ],

    'sponsor_status' => [
        'pending' => 'অপেক্ষমাণ',
        'confirmed' => 'নিশ্চিত',
        'paid' => 'পরিশোধিত',
        'cancelled' => 'বাতিল',
    ],

    'volunteer_status' => [
        'applied' => 'আবেদনকৃত',
        'approved' => 'অনুমোদিত',
        'active' => 'সক্রিয়',
        'inactive' => 'নিষ্ক্রিয়',
        'rejected' => 'প্রত্যাখ্যাত',
    ],

    'assignment_status' => [
        'assigned' => 'নিয়োজিত',
        'confirmed' => 'নিশ্চিত',
        'completed' => 'সম্পন্ন',
        'cancelled' => 'বাতিল',
    ],

    'committee_type' => [
        'executive' => 'কার্যনির্বাহী',
        'organizing' => 'আয়োজক',
        'event' => 'অনুষ্ঠান',
        'finance' => 'অর্থ',
        'media' => 'মিডিয়া',
        'volunteer' => 'স্বেচ্ছাসেবক',
        'batch' => 'ব্যাচ',
    ],

    'committee_member_status' => [
        'active' => 'বর্তমান',
        'past' => 'প্রাক্তন',
    ],

    'post_category' => [
        'general' => 'সাধারণ',
        'reunion' => 'পুনর্মিলনী',
        'batch' => 'ব্যাচ আলোচনা',
        'memories' => 'বিদ্যালয়ের স্মৃতি',
        'career' => 'ক্যারিয়ার',
        'business' => 'ব্যবসা',
        'support' => 'সহযোগিতা',
        'volunteer' => 'স্বেচ্ছাসেবা',
        'jubilee' => 'সুবর্ণজয়ন্তী',
    ],

    'post_status' => [
        'published' => 'প্রকাশিত',
        'hidden' => 'লুকানো',
        'removed' => 'অপসারিত',
    ],

    'comment_status' => [
        'published' => 'প্রকাশিত',
        'hidden' => 'লুকানো',
        'removed' => 'অপসারিত',
    ],

    'reaction_type' => [
        'like' => 'পছন্দ',
        'love' => 'ভালোবাসা',
        'celebrate' => 'উদযাপন',
        'support' => 'সমর্থন',
    ],

    'report_reason' => [
        'spam' => 'স্প্যাম',
        'abuse' => 'অপব্যবহার',
        'false_info' => 'ভুল তথ্য',
        'harassment' => 'হয়রানি',
        'other' => 'অন্যান্য',
    ],

    'report_status' => [
        'open' => 'উন্মুক্ত',
        'reviewing' => 'পর্যালোচনাধীন',
        'resolved' => 'নিষ্পত্তি হয়েছে',
        'dismissed' => 'খারিজ',
    ],

    'content_status' => [
        'draft' => 'খসড়া',
        'published' => 'প্রকাশিত',
        'archived' => 'সংরক্ষিত',
    ],

    'story_status' => [
        'pending' => 'পর্যালোচনার অপেক্ষায়',
        'published' => 'প্রকাশিত',
        'rejected' => 'প্রত্যাখ্যাত',
    ],

    'announcement_kind' => [
        'announcement' => 'ঘোষণা',
        'notice' => 'বিজ্ঞপ্তি',
    ],

    'announcement_level' => [
        'info' => 'তথ্য',
        'important' => 'গুরুত্বপূর্ণ',
        'urgent' => 'জরুরি',
    ],

    'audience_scope' => [
        'public' => 'সর্বসাধারণ',
        'members' => 'সদস্য',
        'batch' => 'ব্যাচ',
        'role' => 'ভূমিকা',
    ],

    'faq_group' => [
        'general' => 'সাধারণ',
        'jubilee' => 'সুবর্ণজয়ন্তী',
        'membership' => 'সদস্যপদ',
        'payment' => 'পেমেন্ট',
        'event' => 'অনুষ্ঠান',
    ],

    'media_collection' => [
        'profile' => 'প্রোফাইল ছবি',
        'gallery' => 'ছবিঘর',
        'cover' => 'কভার',
        'banner' => 'ব্যানার',
        'logo' => 'লোগো',
        'document' => 'নথি',
        'attachment' => 'সংযুক্তি',
    ],

    'campaign_channel' => [
        'mail' => 'ইমেইল',
        'sms' => 'এসএমএস',
        'whatsapp' => 'হোয়াটসঅ্যাপ',
        'database' => 'অ্যাপে',
    ],

    'campaign_status' => [
        'draft' => 'খসড়া',
        'scheduled' => 'নির্ধারিত',
        'sending' => 'পাঠানো হচ্ছে',
        'completed' => 'সম্পন্ন',
        'failed' => 'ব্যর্থ',
        'cancelled' => 'বাতিল',
    ],

    'campaign_recipient_status' => [
        'queued' => 'সারিতে',
        'sent' => 'পাঠানো হয়েছে',
        'delivered' => 'পৌঁছেছে',
        'failed' => 'ব্যর্থ',
        'opened' => 'খোলা হয়েছে',
        'bounced' => 'ফেরত এসেছে',
        'unsubscribed' => 'সাবস্ক্রিপশন বাতিল',
    ],

    'audience_type' => [
        'all_members' => 'সকল সদস্য',
        'batch' => 'ব্যাচ অনুযায়ী',
        'status' => 'অবস্থা অনুযায়ী',
        'role' => 'ভূমিকা অনুযায়ী',
        'custom' => 'কাস্টম ফিল্টার',
    ],

    'setting_group' => [
        'organization' => 'পরিষদ',
        'school' => 'বিদ্যালয়',
        'contact' => 'যোগাযোগ',
        'social' => 'সোশ্যাল লিংক',
        'registration' => 'নিবন্ধন',
        'membership' => 'সদস্যপদ',
        'event' => 'অনুষ্ঠান',
        'jubilee' => 'সুবর্ণজয়ন্তী',
        'notification' => 'বিজ্ঞপ্তি',
        'seo' => 'এসইও',
        'privacy' => 'গোপনীয়তা',
        'system' => 'সিস্টেম',
    ],

];
