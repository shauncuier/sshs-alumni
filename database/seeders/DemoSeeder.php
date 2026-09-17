<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MemberStatus;
use App\Enums\RelationType;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Demo accounts and sample data for development.
 *
 * REFUSES TO RUN IN PRODUCTION. Demo accounts with a published password must
 * never exist on a live deployment, so this guards itself rather than relying
 * on whoever runs the seeder remembering.
 *
 * Every record it creates is marked so `php artisan demo:purge` can find and
 * remove it if a development database is ever promoted.
 *
 * @see docs/11-installation.md section 4
 */
class DemoSeeder extends Seeder
{
    /**
     * Documented in the README as dev-only, and required to be changed before
     * production.
     */
    public const PASSWORD = 'ChangeMe123!';

    /**
     * Stamped on demo members so they can be identified and purged.
     */
    public const MARKER = '[DEMO]';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException(
                'DemoSeeder must never run in production. It creates accounts with a published password.'
            );
        }

        $this->admin();
        $this->approvedMember();
        $this->pendingMember();
        $this->sampleMembers();
    }

    private function admin(): void
    {
        $user = $this->user('admin@example.test', 'Demo Administrator');
        $user->syncRoles(['Super Admin']);
    }

    private function approvedMember(): void
    {
        $user = $this->user('member@example.test', 'Demo Member');
        $user->syncRoles(['Member']);

        Member::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'full_name' => 'Demo Member '.self::MARKER,
                'relation_type' => RelationType::FormerStudent,
                'batch_id' => Batch::query()->where('ssc_year', 2000)->value('id'),
                'ssc_year' => 2000,
                'email' => $user->email,
                'mobile' => '01712345678',
                'status' => MemberStatus::Approved,
                'membership_no' => 'SSHS-2000-0001',
                'verified_at' => now(),
                'occupation' => 'Engineer',
                'organization' => 'Demo Organisation',
                'district' => 'Chattogram',
                'city' => 'Sitakunda',
            ],
        );
    }

    private function pendingMember(): void
    {
        $user = $this->user('pending@example.test', 'Demo Pending Member');
        $user->syncRoles(['Member']);

        Member::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'full_name' => 'Demo Pending '.self::MARKER,
                'relation_type' => RelationType::FormerStudent,
                'batch_id' => Batch::query()->where('ssc_year', 2010)->value('id'),
                'ssc_year' => 2010,
                'email' => $user->email,
                'status' => MemberStatus::Pending,
            ],
        );
    }

    /**
     * A spread of members across batches, so the directory, filters and batch
     * counters have something realistic to work against.
     */
    private function sampleMembers(): void
    {
        if (Member::query()->count() > 10) {
            return;
        }

        $batches = Batch::query()->inRandomOrder()->limit(15)->get();

        foreach ($batches as $batch) {
            Member::factory()
                ->count(8)
                ->create([
                    'batch_id' => $batch->id,
                    'ssc_year' => $batch->ssc_year,
                    'status' => MemberStatus::Approved,
                    'user_id' => null,
                ])
                ->each(function (Member $member): void {
                    $member->forceFill([
                        'full_name' => $member->full_name.' '.self::MARKER,
                    ])->save();
                });
        }
    }

    private function user(string $email, string $name): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ],
        );

        return $user;
    }
}
