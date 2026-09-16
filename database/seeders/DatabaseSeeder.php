<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Default seeder: everything production needs, plus demo data outside
 * production.
 *
 * DemoSeeder refuses to run when APP_ENV=production, so this entry point is
 * safe even if someone runs `db:seed` on a live server by mistake.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductionSeeder::class);

        if (app()->isProduction()) {
            return;
        }

        // Announced here rather than inside DemoSeeder, because that seeder is
        // also invoked directly (in tests) where no command is attached.
        $this->command->warn('Seeding DEMO data — development only. Run `php artisan demo:purge` before going live.');

        $this->call(DemoSeeder::class);
    }
}
