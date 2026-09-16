<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Removes demo accounts and sample members.
 *
 * Needed when a development database is promoted to production: demo accounts
 * with a published password must not survive the move.
 *
 * Destructive, so it confirms first and reports exactly what it will delete.
 *
 * @see docs/11-installation.md section 4
 */
class DemoPurgeCommand extends Command
{
    protected $signature = 'demo:purge {--force : Skip the confirmation prompt}';

    protected $description = 'Delete demo accounts and sample data';

    public function handle(): int
    {
        $users = User::withTrashed()->where('email', 'like', '%@example.test')->get();
        $members = Member::withTrashed()->where('full_name', 'like', '%'.DemoSeeder::MARKER.'%')->get();

        if ($users->isEmpty() && $members->isEmpty()) {
            $this->components->info('No demo data found. Nothing to purge.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->twoColumnDetail('Demo users', (string) $users->count());
        $this->components->twoColumnDetail('Demo members', (string) $members->count());
        $this->newLine();

        foreach ($users as $user) {
            $this->line("  <fg=gray>user</>   {$user->email}");
        }

        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('Permanently delete these records?', false)) {
            $this->components->info('Cancelled. Nothing was deleted.');

            return self::SUCCESS;
        }

        // One transaction: a half-purged database would leave orphaned members
        // whose user is gone.
        DB::transaction(function () use ($users, $members): void {
            foreach ($members as $member) {
                $member->forceDelete();
            }

            foreach ($users as $user) {
                $user->forceDelete();
            }
        });

        $this->newLine();
        $this->components->info(
            "Purged {$users->count()} user(s) and {$members->count()} member(s)."
        );

        return self::SUCCESS;
    }
}
