<?php

declare(strict_types=1);

use App\Enums\CommentStatus;
use App\Enums\PostStatus;
use App\Enums\ReactionType;
use App\Enums\ReportStatus;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\Member;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use App\Services\Community\ReactionToggler;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;

/**
 * Reactions, reports and the moderation queue.
 *
 * The invariants: one reaction per member per item enforced by the database, a
 * report that changes nothing on its own, and a moderation action that cannot
 * happen without an audit row naming who did it.
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * One shared batch — `batches.ssc_year` is UNIQUE and the factory picks the
 * year at random, so a batch per member eventually collides.
 */
function moderationBatch(): Batch
{
    return Batch::query()->first() ?? Batch::factory()->create();
}

function moderator(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Moderator']);
    Member::factory()->approved()->for(moderationBatch())->create(['user_id' => $user->id]);

    return $user;
}

/**
 * An approved member with a login. Defined here rather than reused from
 * FeedTest: a Pest helper is only declared once the file that declares it has
 * been loaded, so a single-file run of this test would not find it.
 */
function feedMember(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Member']);
    Member::factory()->approved()->for(moderationBatch())->create(['user_id' => $user->id]);

    return $user;
}

function communityPost(): Post
{
    return Post::factory()->create([
        'author_member_id' => Member::factory()->approved()->for(moderationBatch())->create()->id,
    ]);
}

describe('reactions', function (): void {
    it('keeps one reaction per member per item', function (): void {
        $post = communityPost();
        $member = Member::factory()->approved()->for(moderationBatch())->create();

        $toggler = app(ReactionToggler::class);

        $toggler->toggle($post, $member, ReactionType::Like);
        $toggler->toggle($post, $member, ReactionType::Love);

        expect(Reaction::query()->count())->toBe(1)
            ->and(Reaction::query()->first()?->type)->toBe(ReactionType::Love);
    });

    it('removes the reaction when the same one is tapped twice', function (): void {
        $post = communityPost();
        $member = Member::factory()->approved()->for(moderationBatch())->create();

        $toggler = app(ReactionToggler::class);

        $toggler->toggle($post, $member, ReactionType::Like);
        $standing = $toggler->toggle($post, $member, ReactionType::Like);

        expect($standing)->toBeNull()
            ->and(Reaction::query()->count())->toBe(0)
            ->and($post->fresh()?->reactions_count)->toBe(0);
    });

    it('counts a reaction on the post', function (): void {
        $post = communityPost();
        $user = feedMember();

        $this->actingAs($user)
            ->post("/community/{$post->ulid}/reactions", ['type' => ReactionType::Celebrate->value])
            ->assertRedirect();

        expect($post->fresh()?->reactions_count)->toBe(1);
    });
});

describe('reporting', function (): void {
    it('files a report without hiding anything', function (): void {
        $post = communityPost();

        $this->actingAs(feedMember())
            ->post("/community/{$post->ulid}/reports", ['reason' => 'spam'])
            ->assertRedirect();

        // If a report took content down on its own, the community would have
        // been handed a delete button for anybody with a grudge.
        expect(ContentReport::query()->count())->toBe(1)
            ->and($post->fresh()?->status)->toBe(PostStatus::Published);
    });

    it('does not file the same report twice', function (): void {
        $post = communityPost();
        $user = feedMember();

        $this->actingAs($user)->post("/community/{$post->ulid}/reports", ['reason' => 'spam']);
        $this->actingAs($user)->post("/community/{$post->ulid}/reports", ['reason' => 'abuse']);

        expect(ContentReport::query()->count())->toBe(1);
    });

    it('will not let somebody report their own post', function (): void {
        $user = feedMember();
        $post = Post::factory()->create(['author_member_id' => $user->member->id]);

        // They can simply delete it.
        $this->actingAs($user)
            ->post("/community/{$post->ulid}/reports", ['reason' => 'spam'])
            ->assertForbidden();
    });
});

describe('the queue', function (): void {
    it('is closed to an ordinary member', function (): void {
        $this->actingAs(feedMember())
            ->get('/admin/community/reports')
            ->assertForbidden();
    });

    it('opens for a moderator', function (): void {
        ContentReport::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => communityPost()->id,
        ]);

        $this->actingAs(moderator())
            ->get('/admin/community/reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/community/reports')
                ->has('reports.data', 1)
            );
    });

    it('audits hiding a post, with the reason', function (): void {
        $post = communityPost();
        $moderator = moderator();

        $this->actingAs($moderator)->put("/admin/community/posts/{$post->ulid}", [
            'status' => PostStatus::Hidden->value,
            'reason' => 'Repeated advertising',
        ]);

        $audit = AuditLog::query()->where('action', 'moderation.post.hidden')->first();

        expect($post->fresh()?->status)->toBe(PostStatus::Hidden)
            ->and($audit)->not->toBeNull()
            ->and($audit?->user_id)->toBe($moderator->id)
            ->and($audit?->description)->toBe('Repeated advertising');
    });

    it('drops a hidden comment out of the count', function (): void {
        $post = communityPost();

        $comment = Comment::factory()->create([
            'commentable_type' => $post->getMorphClass(),
            'commentable_id' => $post->id,
        ]);

        expect($post->fresh()?->comments_count)->toBe(1);

        $this->actingAs(moderator())->put("/admin/community/comments/{$comment->id}", [
            'status' => CommentStatus::Hidden->value,
        ]);

        // The number under the post has to match what a reader can see.
        expect($post->fresh()?->comments_count)->toBe(0);
    });

    it('closes every other open report on the same item', function (): void {
        $post = communityPost();

        $reports = ContentReport::factory()->count(3)->create([
            'reportable_type' => $post->getMorphClass(),
            'reportable_id' => $post->id,
        ]);

        $this->actingAs(moderator())->put("/admin/community/reports/{$reports[0]->id}", [
            'status' => ReportStatus::Dismissed->value,
            'note' => 'Looked, nothing wrong with it',
        ]);

        // Two moderators cannot work the same post twice.
        expect(ContentReport::query()->where('status', ReportStatus::Open)->count())->toBe(0);
    });

    it('keeps a dismissed report on the record', function (): void {
        $post = communityPost();

        $report = ContentReport::factory()->create([
            'reportable_type' => $post->getMorphClass(),
            'reportable_id' => $post->id,
        ]);

        $moderator = moderator();

        $this->actingAs($moderator)->put("/admin/community/reports/{$report->id}", [
            'status' => ReportStatus::Dismissed->value,
        ]);

        // A queue that can be emptied by deleting the awkward entries is not a
        // record of anything.
        $fresh = $report->fresh();

        expect($fresh)->not->toBeNull()
            ->and($fresh?->status)->toBe(ReportStatus::Dismissed)
            ->and($fresh?->resolved_by)->toBe($moderator->id);
    });

    it('still lists a post whose author has since been removed', function (): void {
        $post = communityPost();

        ContentReport::factory()->create([
            'reportable_type' => $post->getMorphClass(),
            'reportable_id' => $post->id,
        ]);

        $post->author->delete();

        // A member can be soft-deleted while their posts stand, and the queue
        // is needed exactly when somebody is cleaning up after a departure.
        $this->actingAs(moderator())
            ->get('/admin/community/posts')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('posts.data', 1));

        $this->actingAs(moderator())
            ->get('/admin/community/reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('reports.data', 1));
    });

    it('has no route that deletes a report', function (): void {
        $names = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route): ?string => $route->getName())
            ->filter()
            ->filter(fn (string $name): bool => str_contains($name, 'community.reports'))
            ->values();

        expect($names->contains('admin.community.reports.destroy'))->toBeFalse();
    });
});
