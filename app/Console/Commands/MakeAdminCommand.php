<?php

declare(strict_types=1);

namespace App\Console\Commands;

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
        {--role=Super Admin : Role to assign}';

    protected $description = 'Create an administrator account';

    public function handle(): int
    {
        if (Role::query()->count() === 0) {
            $this->components->error(
                'No roles exist yet. Run `php artisan db:seed --class=RolePermissionSeeder` first.'
            );

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Full name');
        $email = $this->option('email') ?: $this->ask('Email address');
        $role = (string) $this->option('role');

        // secret() keeps the password off the screen and out of shell history.
        $password = $this->secret('Password');
        $confirmation = $this->secret('Confirm password');

        if ($password !== $confirmation) {
            $this->components->error('The passwords do not match.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'role' => $role],
            [
                'name' => ['required', 'string', 'max:150'],
                'email' => ['required', 'email', 'max:191', 'unique:users,email'],
                // Production rules apply: min 12 with mixed case, numbers,
                // symbols, and checked against known breaches.
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

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
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
