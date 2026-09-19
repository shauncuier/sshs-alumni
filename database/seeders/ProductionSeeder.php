<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Everything a production deployment needs, and nothing it does not.
 *
 * Safe to run on a live database: every seeder it calls is idempotent and none
 * overwrites an administrator's edits.
 *
 *     php artisan db:seed --class=ProductionSeeder --force
 *
 * @see docs/11-installation.md section 6
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            BatchSeeder::class,
            SchoolHistorySeeder::class,
            JubileeSeeder::class,
            ReferenceDataSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}
