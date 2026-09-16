<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SchoolMilestone;
use Illuminate\Database\Seeder;

/**
 * The school-history timeline.
 *
 * Two verified milestones are seeded and the timeline is left open for the
 * committee to extend without limit.
 *
 * Seeding BOTH founding years is what keeps the story honest: the fifty years
 * belong to the school (1976), and the association (2015) is the body
 * organising the celebration. A timeline showing only 1976 would imply the
 * association is fifty years old, which it is not.
 *
 * @see docs/17-golden-jubilee.md section 6
 */
class SchoolHistorySeeder extends Seeder
{
    public function run(): void
    {
        $milestones = [
            [
                'year' => (int) (setting('school.established') ?? config('organization.school.established')),
                'title' => 'School journey begins',
                'title_bn' => 'বিদ্যালয়ের পথচলা শুরু',
                'description' => 'Sabuj Shikshayatan Government High School is established at Baro Aulia, Sitakunda, Chattogram.',
                'description_bn' => 'সীতাকুণ্ড, চট্টগ্রামের বাড়ৈ আউলিয়ায় সবুজ শিক্ষায়তন উচ্চ বিদ্যালয় প্রতিষ্ঠিত হয়।',
                'display_order' => 1,
                'is_highlighted' => true,
            ],
            [
                'year' => (int) (setting('organization.established') ?? config('organization.association.established')),
                'title' => 'Former Students Association founded',
                'title_bn' => 'প্রাক্তন ছাত্র-ছাত্রী পরিষদ প্রতিষ্ঠা',
                'description' => 'Alumni of the school form the Former Students Association to keep the community connected.',
                'description_bn' => 'বিদ্যালয়ের প্রাক্তন শিক্ষার্থীরা মিলে প্রাক্তন ছাত্র-ছাত্রী পরিষদ গঠন করেন।',
                'display_order' => 2,
                'is_highlighted' => true,
            ],
        ];

        foreach ($milestones as $milestone) {
            SchoolMilestone::query()->firstOrCreate(
                ['year' => $milestone['year'], 'title' => $milestone['title']],
                $milestone,
            );
        }
    }
}
