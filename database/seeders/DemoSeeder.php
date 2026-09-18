<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MemberStatus;
use App\Enums\PostCategory;
use App\Enums\ReactionType;
use App\Enums\RelationType;
use App\Models\Batch;
use App\Models\Comment;
use App\Models\Member;
use App\Models\Post;
use App\Models\Reaction;
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
        $this->communityPosts();
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

    /**
     * A handful of posts, so the feed, the thread and the moderation queue
     * have something in them.
     *
     * One is a batch post, because the batch rule is the thing most worth
     * seeing work: it should be invisible to everybody outside that cohort.
     */
    private function communityPosts(): void
    {
        if (Post::query()->exists()) {
            return;
        }

        $author = Member::query()
            ->whereNotNull('user_id')
            ->where('status', MemberStatus::Approved)
            ->first();

        if ($author === null) {
            return;
        }

        $others = Member::query()
            ->where('status', MemberStatus::Approved)
            ->whereKeyNot($author->id)
            ->limit(3)
            ->get();

        $seed = [
            [PostCategory::Memories, 'The old assembly ground', 'Does anybody have photographs of the assembly ground before the new block went up?'],
            [PostCategory::Jubilee, 'Golden Jubilee volunteers', 'We need help with registration on the day. Reply here if you can spare a morning.'],
            [PostCategory::Career, 'Graduates looking for placements', 'If your organisation takes interns, say so here. Several of our recent batches are looking.'],
        ];

        foreach ($seed as [$category, $title, $body]) {
            $post = Post::query()->create([
                'author_member_id' => $author->id,
                'category' => $category,
                'title' => $title,
                'body' => $body,
                'comments_enabled' => true,
            ]);

            foreach ($others as $index => $member) {
                Comment::query()->create([
                    'commentable_type' => $post->getMorphClass(),
                    'commentable_id' => $post->id,
                    'author_member_id' => $member->id,
                    'body' => 'Demo comment '.($index + 1).' '.self::MARKER,
                ]);

                Reaction::query()->firstOrCreate([
                    'reactable_type' => $post->getMorphClass(),
                    'reactable_id' => $post->id,
                    'member_id' => $member->id,
                ], ['type' => ReactionType::Like]);
            }
        }

        if ($author->batch_id !== null) {
            Post::query()->create([
                'author_member_id' => $author->id,
                'category' => PostCategory::Batch,
                'batch_id' => $author->batch_id,
                'title' => 'Batch meetup',
                'body' => 'Only our batch sees this one. Who is free on the last Friday of the month?',
                'comments_enabled' => true,
            ]);
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
