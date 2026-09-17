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
 * null, so every public surface renders the "to be announced" line and no
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
                'summary' => '50th Anniversary of Sabuj Shikshayatan Government High School and alumni reunion.',
                'description' => 'Fifty years of the school, 1976 to 2026, celebrated by the Former Students Association.',

                // The date rule, in data.
                'date_status' => EventDateStatus::Tba,
                'starts_at' => null,
                'ends_at' => null,

                'registration_required' => true,
                'currency' => 'BDT',
                'organizer_name' => (string) setting('organization.name_en', 'Former Students Association'),
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
                'answer' => 'The date has not been fixed yet. It will be announced here as soon as the committee decides.',
                'display_order' => 1,
            ],
            [
                'question' => 'Who can attend?',
                'answer' => 'All former students and teachers of the school, along with invited guests.',
                'display_order' => 2,
            ],
            [
                'question' => 'How do I register?',
                'answer' => 'Become a member of the association first, then register for the event from your dashboard.',
                'display_order' => 3,
            ],
            [
                'question' => 'Is the school 50 years old, or the association?',
                'answer' => 'The school was established in 1976, so 1976-2026 marks the school\'s fifty years. The Former Students Association, founded in 2015, is organising the celebration.',
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
