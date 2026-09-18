<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AnnouncementKind;
use App\Enums\AnnouncementLevel;
use App\Enums\AudienceScope;
use App\Enums\ContentStatus;
use App\Enums\MemberStatus;
use App\Enums\PostCategory;
use App\Enums\ReactionType;
use App\Enums\RelationType;
use App\Enums\StoryStatus;
use App\Models\AlumniStory;
use App\Models\Announcement;
use App\Models\Batch;
use App\Models\Comment;
use App\Models\GalleryAlbum;
use App\Models\Member;
use App\Models\News;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use App\Support\SlugFactory;
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
        $this->content();
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

    /**
     * A little published content, so the home page and the public site are not
     * a set of empty sections in development.
     *
     * Everything here is marked [DEMO] and removed by `demo:purge`.
     */
    private function content(): void
    {
        if (News::query()->exists()) {
            return;
        }

        $author = User::query()->where('email', 'admin@example.test')->first();

        $articles = [
            [
                'title' => 'Golden Jubilee planning is under way',
                'excerpt' => 'The committee has begun work on the fiftieth anniversary programme.',
                'category' => 'Jubilee',
                'is_featured' => true,
            ],
            [
                'title' => 'Scholarship fund reaches its first target',
                'excerpt' => 'Contributions from three batches have funded the first year of support.',
                'category' => 'Association',
                'is_featured' => false,
            ],
        ];

        foreach ($articles as $article) {
            $news = News::query()->create([
                ...$article,
                'slug' => SlugFactory::unique(News::class, $article['title'], 'news'),
                'body' => $article['excerpt'].' '.self::MARKER,
                'author_id' => $author?->id,
                'status' => ContentStatus::Published,
                'published_at' => now()->subDays(count($articles)),
            ]);

            unset($news);
        }

        Announcement::query()->create([
            'kind' => AnnouncementKind::Notice,
            'title' => 'Office hours during the holidays '.self::MARKER,
            'body' => 'The association office is open on Sunday and Tuesday mornings only.',
            'level' => AnnouncementLevel::Info,
            'audience' => AudienceScope::Public,
            'status' => ContentStatus::Published,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'published_by' => $author?->id,
        ]);

        $album = GalleryAlbum::query()->create([
            'slug' => SlugFactory::unique(GalleryAlbum::class, 'Reunion photographs', 'album'),
            'title' => 'Reunion photographs '.self::MARKER,
            'description' => 'Pictures from the last association gathering.',
            'status' => ContentStatus::Published,
            'published_at' => now()->subWeek(),
        ]);

        unset($album);

        $member = Member::query()
            ->whereNotNull('user_id')
            ->where('status', MemberStatus::Approved)
            ->first();

        if ($member !== null) {
            AlumniStory::query()->create([
                'slug' => SlugFactory::unique(AlumniStory::class, 'What the school gave me', 'story'),
                'member_id' => $member->id,
                'author_name' => $member->full_name,
                'batch_id' => $member->batch_id,
                'title' => 'What the school gave me',
                'body' => 'A short demo story. '.self::MARKER.' '.str_repeat('It goes on for a paragraph or so. ', 8),
                'career_summary' => 'Engineer',
                'status' => StoryStatus::Published,
                'published_at' => now()->subDays(3),
                'is_featured' => true,
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
