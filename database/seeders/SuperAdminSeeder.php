<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MemberStatus;
use App\Enums\RelationType;
use App\Enums\UserStatus;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Provisions or updates the Super Administrator from environment configuration.
 *
 * Runs automatically during database seeding if SUPER_ADMIN_EMAIL is configured.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('auth.super_admin.email');

        if (empty($email)) {
            return;
        }

        if (Role::query()->where('name', 'Super Admin')->doesntExist()) {
            return;
        }

        $password = (string) config('auth.super_admin.password', 'AdminPassword123!@#');
        $name = (string) config('auth.super_admin.name', 'Super Administrator');
        $phone = config('auth.super_admin.phone');

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'status' => UserStatus::Active,
                'phone' => $phone ?: null,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['Super Admin']);

        // Ensure Super Admin has an approved Member record for member surfaces
        $batch = Batch::query()->where('ssc_year', 2000)->first()
            ?? Batch::query()->orderBy('ssc_year')->first();

        Member::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'full_name' => $name,
                'relation_type' => RelationType::FormerStudent,
                'batch_id' => $batch?->id,
                'ssc_year' => $batch?->ssc_year ?? 2000,
                'email' => $user->email,
                'mobile' => $phone ?: '01711223344',
                'status' => MemberStatus::Approved,
                'membership_no' => 'SSHS-ADMIN-0001',
                'verified_at' => now(),
                'occupation' => 'System Administrator',
                'organization' => 'SSHS Alumni Association',
                'district' => 'Chattogram',
                'city' => 'Sitakunda',
            ]
        );
    }
}
