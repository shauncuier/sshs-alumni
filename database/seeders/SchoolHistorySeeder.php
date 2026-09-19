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
                'description' => 'Sabuj Shikshayatan Government High School is established on 1 January 1976 at Hafiz Jute Mills, Baro Aulia, Sitakunda, Chattogram.',
                'display_order' => 1,
                'is_highlighted' => true,
            ],
            [
                'year' => (int) (setting('organization.established') ?? config('organization.association.established')),
                'title' => 'Former Students Association founded',
                'description' => 'Alumni of the school form the Former Students Association (প্রাক্তন ছাত্র-ছাত্রী পরিষদ) to unite graduates across SSC batches and foster student welfare.',
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
