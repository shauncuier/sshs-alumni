<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BatchStatus;
use App\Models\Batch;
use Illuminate\Database\Seeder;

/**
 * SSC batches.
 *
 * The school opened in 1976, and the first cohort to sit SSC did so roughly
 * five years later — so batches run from 1981 to the current year.
 *
 * Idempotent, and safe to re-run each year to add the newest batch.
 *
 * @see docs/02-database-schema.md section 5
 */
class BatchSeeder extends Seeder
{
    public function run(): void
    {
        // Derived from the school's founding year rather than hard-coded, so
        // a correction to the founding date flows through to the batch list.
        $established = (int) (setting('school.established')
            ?? config('organization.school.established'));

        $firstSscYear = $established + (int) config('organization.years_to_first_ssc');
        $currentYear = (int) now()->year;

        for ($year = $firstSscYear; $year <= $currentYear; $year++) {
            Batch::query()->firstOrCreate(
                ['ssc_year' => $year],
                [
                    'slug' => "ssc-{$year}",
                    'name' => "SSC {$year}",
                    'status' => BatchStatus::Active,
                ],
            );
        }
    }
}
