<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Organization & school
|--------------------------------------------------------------------------
|
| Bootstrap values for the first seed. After that these live in the database
| (settings groups `organization` and `school`) and are edited in the admin
| panel, which takes precedence.
|
| These are read through config() rather than env() at the point of use,
| because env() returns null once `php artisan config:cache` has run — which
| production always does. Seeding a cached install straight from env() would
| silently write empty values.
|
| The association (2015) and the school (1976) are deliberately separate. The
| fifty years being celebrated are the SCHOOL's; the association is the
| organiser. See docs/00-overview.md section 3.
|
*/

return [

    'association' => [
        'name_bn' => env('ORG_NAME_BN', 'প্রাক্তন ছাত্র-ছাত্রী পরিষদ'),
        'name_en' => env('ORG_NAME_EN', 'Former Students Association'),
        'short_name' => env('ORG_SHORT_NAME', 'SSHS Alumni'),
        'established' => (int) env('ORG_ESTABLISHED', 2015),
        'contact_email' => env('ORG_CONTACT_EMAIL', ''),
        'contact_phone' => env('ORG_CONTACT_PHONE', ''),
    ],

    'school' => [
        'name_bn' => env('SCHOOL_NAME_BN', 'সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয়'),
        'name_en' => env('SCHOOL_NAME_EN', 'Sabuj Shikshayatan Government High School'),
        'established' => (int) env('SCHOOL_ESTABLISHED', 1976),
        'eiin' => env('SCHOOL_EIIN', '105070'),
        'address' => env('SCHOOL_ADDRESS', 'Hafiz Jute Mills Ltd, Baro Aulia, Sitakunda, Chattogram'),
        'phone' => env('SCHOOL_PHONE', ''),
        'email' => env('SCHOOL_EMAIL', ''),
        'website' => env('SCHOOL_WEBSITE', 'https://sabujsghs.edu.bd'),
        'board' => env('SCHOOL_BOARD', 'Board of Intermediate and Secondary Education, Chattogram'),
        // The school's motto is a set phrase in Bangla, with its sense in
        // English underneath. See docs/06-localization.md section 2.
        'motto_bn' => env('SCHOOL_MOTTO_BN', 'জ্ঞানই শক্তি'),
        'motto_en' => env('SCHOOL_MOTTO_EN', 'Knowledge is Power'),

        /*
         * Office holders change. These seed the initial values only; after
         * that they are edited in the admin panel, so updating them never
         * requires a redeploy.
         */
        'head_teacher' => env('SCHOOL_HEAD_TEACHER', 'Nurjahan Akter'),
        'chairperson' => env('SCHOOL_CHAIRPERSON', 'Md. Jasim Uddin'),
    ],

    /*
     * Years from the school's founding to its first SSC cohort, used to
     * generate the batch list.
     */
    'years_to_first_ssc' => (int) env('YEARS_TO_FIRST_SSC', 5),

];
