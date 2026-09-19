<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

/**
 * Creates the first real administrator.
 *
 * This is the supported way to create an admin — not editing the database by
 * hand, and not leaving the demo account in place.
 *
 * @see docs/11-installation.md section 5
 */
class MakeAdminCommand extends Command
{
    protected $signature = 'make:admin
        {--name= : Full name}
        {--email= : Email address}
        {--password= : Password}
        {--role=Super Admin : Role to assign}
        {--from-env : Provision or sync admin using .env configuration}';

    protected $description = 'Create or provision an administrator account';

    public function handle(): int
    {
        if (Role::query()->count() === 0) {
            $this->components->error(
                'No roles exist yet. Run `php artisan db:seed --class=RolePermissionSeeder` first.'
            );

            return self::FAILURE;
        }

        $fromEnv = (bool) $this->option('from-env');

        $name = $this->option('name') ?: ($fromEnv ? config('auth.super_admin.name') : $this->ask('Full name'));
        $email = $this->option('email') ?: ($fromEnv ? config('auth.super_admin.email') : $this->ask('Email address'));
        $role = (string) $this->option('role');

        if ($fromEnv) {
            $password = $this->option('password') ?: (string) config('auth.super_admin.password');
        } elseif ($this->option('password')) {
            $password = (string) $this->option('password');
        } else {
            // secret() keeps the password off the screen and out of shell history.
            $password = $this->secret('Password');
            $confirmation = $this->secret('Confirm password');

            if ($password !== $confirmation) {
                $this->components->error('The passwords do not match.');

                return self::FAILURE;
            }
        }

        $existingUser = User::query()->where('email', $email)->first();

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'role' => $role],
            [
                'name' => ['required', 'string', 'max:150'],
                'email' => ['required', 'email', 'max:191'],
                'password' => ['required', Password::defaults()],
                'role' => ['required', 'exists:roles,name'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        if ($existingUser !== null) {
            $existingUser->update([
                'name' => $name,
                'password' => Hash::make($password),
                'status' => UserStatus::Active,
            ]);
            $existingUser->syncRoles([$role]);

            $this->newLine();
            $this->components->info("Administrator updated: {$email} ({$role})");

            return self::SUCCESS;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'status' => UserStatus::Active,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();
        $user->syncRoles([$role]);

        $this->newLine();
        $this->components->info("Administrator created: {$email} ({$role})");

        if (User::query()->where('email', 'like', '%@example.test')->exists()) {
            $this->newLine();
            $this->components->warn(
                'Demo accounts still exist on this database. Run `php artisan demo:purge` before going live.'
            );
        }

        return self::SUCCESS;
    }
}
