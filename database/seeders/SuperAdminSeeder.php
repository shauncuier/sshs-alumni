<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserStatus;
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
    }
}
