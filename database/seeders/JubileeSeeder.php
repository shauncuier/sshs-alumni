<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EventDateStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * The Golden Jubilee, seeded as the FLAGSHIP EVENT — one row in the general
 * event system, not a separate subsystem that becomes dead code in 2027.
 *
 * THE DATE IS NOT SET, AND THAT IS DELIBERATE.
 *
 * The committee has not fixed one. `date_status` is `tba` and `starts_at` is
 * null, so every public surface renders "তারিখ শীঘ্রই ঘোষণা করা হবে" and no
 * countdown is rendered at all. An administrator publishes the date later from
 * /admin/jubilee, with no deployment.
 *
 * No date literal for the Jubilee exists anywhere in this codebase.
 *
 * @see docs/17-golden-jubilee.md
 */
class JubileeSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::query()->firstOrCreate(
            ['slug' => 'golden-jubilee-2026'],
            [
                'type' => EventType::Reunion,
                'title' => 'Golden Jubilee 2026',
                'title_bn' => 'সুবর্ণজয়ন্তী ২০২৬',
                'summary' => '50th Anniversary of Sabuj Shikshayatan Government High School and alumni reunion.',
                'summary_bn' => 'সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয়ের সুবর্ণজয়ন্তী ও প্রাক্তন শিক্ষার্থীদের পুনর্মিলনী।',
                'description' => 'Fifty years of the school, 1976 to 2026, celebrated by the Former Students Association.',
                'description_bn' => '১৯৭৬ থেকে ২০২৬ — বিদ্যালয়ের ৫০ বছর। আয়োজনে প্রাক্তন ছাত্র-ছাত্রী পরিষদ।',

                // The date rule, in data.
                'date_status' => EventDateStatus::Tba,
                'starts_at' => null,
                'ends_at' => null,

                'registration_required' => true,
                'currency' => 'BDT',
                'organizer_name' => (string) setting('organization.name_bn', 'প্রাক্তন ছাত্র-ছাত্রী পরিষদ'),
                'status' => EventStatus::Published,
                'is_flagship' => true,
                'published_at' => now(),
            ],
        );

        $this->seedFaqs($event);
    }

    private function seedFaqs(Event $event): void
    {
        $faqs = [
            [
                'question' => 'When is the Golden Jubilee?',
                'question_bn' => 'সুবর্ণজয়ন্তী কবে অনুষ্ঠিত হবে?',
                'answer' => 'The date has not been fixed yet. It will be announced here as soon as the committee decides.',
                'answer_bn' => 'তারিখ এখনও নির্ধারিত হয়নি। কমিটি সিদ্ধান্ত নেওয়ার সঙ্গে সঙ্গে এখানে জানানো হবে।',
                'display_order' => 1,
            ],
            [
                'question' => 'Who can attend?',
                'question_bn' => 'কারা অংশ নিতে পারবেন?',
                'answer' => 'All former students and teachers of the school, along with invited guests.',
                'answer_bn' => 'বিদ্যালয়ের সকল প্রাক্তন শিক্ষার্থী ও শিক্ষক, এবং আমন্ত্রিত অতিথিবৃন্দ।',
                'display_order' => 2,
            ],
            [
                'question' => 'How do I register?',
                'question_bn' => 'কীভাবে নিবন্ধন করব?',
                'answer' => 'Become a member of the association first, then register for the event from your dashboard.',
                'answer_bn' => 'প্রথমে পরিষদের সদস্য হোন, এরপর আপনার ড্যাশবোর্ড থেকে অনুষ্ঠানের জন্য নিবন্ধন করুন।',
                'display_order' => 3,
            ],
            [
                'question' => 'Is the school 50 years old, or the association?',
                'question_bn' => 'বিদ্যালয়ের ৫০ বছর, নাকি পরিষদের?',
                'answer' => 'The school was established in 1976, so 1976-2026 marks the school\'s fifty years. The Former Students Association, founded in 2015, is organising the celebration.',
                'answer_bn' => 'বিদ্যালয় প্রতিষ্ঠিত হয় ১৯৭৬ সালে, তাই ১৯৭৬–২০২৬ বিদ্যালয়ের ৫০ বছর। ২০১৫ সালে প্রতিষ্ঠিত প্রাক্তন ছাত্র-ছাত্রী পরিষদ এই আয়োজন করছে।',
                'display_order' => 4,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::query()->firstOrCreate(
                ['group' => 'jubilee', 'question' => $faq['question']],
                [...$faq, 'group' => 'jubilee', 'is_published' => true],
            );
        }
    }
}
