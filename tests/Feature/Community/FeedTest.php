<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Enums\PostCategory;
use App\Enums\PostStatus;
use App\Models\Batch;
use App\Models\Comment;
use App\Models\Member;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * The community feed: who may read it, and what they may see in it.
 *
 * The two rules under test are the ones that cannot be got wrong: a pending
 * application does not get in at all, and a batch post belongs to that batch.
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * One shared batch.
 *
 * `batches.ssc_year` is UNIQUE and the factory picks a year at random, so a
 * test that creates a batch per member eventually collides. Everything that
 * does not care which batch it is in shares this one.
 */
function communityBatch(): Batch
{
    return Batch::query()->first() ?? Batch::factory()->create();
}

/**
 * An approved member with a login, optionally in a batch.
 */
function communityMember(?Batch $batch = null): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    Member::factory()
        ->approved()
        ->for($batch ?? communityBatch())
        ->create(['user_id' => $user->id]);

    return $user;
}

describe('access', function (): void {
    it('is closed to a guest', function (): void {
        $this->get('/community')->assertRedirect('/login');
    });

    it('is closed to a member whose application is still pending', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);
        Member::factory()->for(communityBatch())->create([
            'user_id' => $user->id,
            // Explicit: the factory rolls a random status, and this test is
            // about one particular one.
            'status' => MemberStatus::Pending,
        ]);

        $this->actingAs($user)
            ->get('/community')
            ->assertRedirect('/my/profile');
    });

    it('opens for an approved member', function (): void {
        $this->actingAs(communityMember())
            ->get('/community')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('member/community'));
    });
});

describe('what the feed shows', function (): void {
    it('lists published posts', function (): void {
        Post::factory()->count(3)->create([
            'author_member_id' => Member::factory()->approved()->for(communityBatch())->create()->id,
        ]);

        $this->actingAs(communityMember())
            ->get('/community')
            ->assertInertia(fn ($page) => $page->has('posts.data', 3));
    });

    it('hides a post a moderator has hidden', function (): void {
        Post::factory()->hidden()->create([
            'author_member_id' => Member::factory()->approved()->for(communityBatch())->create()->id,
        ]);

        $this->actingAs(communityMember())
            ->get('/community')
            ->assertInertia(fn ($page) => $page->has('posts.data', 0));
    });

    it('still shows an author their own hidden post', function (): void {
        $user = communityMember();

        Post::factory()->hidden()->create([
            'author_member_id' => $user->member->id,
        ]);

        // Being moderated is not the same as being lied to about whether your
        // words still exist.
        $this->actingAs($user)
            ->get('/community')
            ->assertInertia(fn ($page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.status', PostStatus::Hidden->value)
            );
    });

    it('keeps a batch post inside its batch', function (): void {
        // Explicit years: `batches.ssc_year` is UNIQUE and the factory picks
        // one at random, so two factory batches collide roughly one run in
        // fifteen.
        $batch = Batch::factory()->create(['ssc_year' => 1999]);
        $other = Batch::factory()->create(['ssc_year' => 2001]);

        Post::factory()
            ->forBatch($batch->id)
            ->create(['author_member_id' => Member::factory()->approved()->for($batch)->create()->id]);

        $this->actingAs(communityMember($batch))
            ->get('/community')
            ->assertInertia(fn ($page) => $page->has('posts.data', 1));

        $this->actingAs(communityMember($other))
            ->get('/community')
            ->assertInertia(fn ($page) => $page->has('posts.data', 0));
    });

    it('404s a batch post opened by somebody outside the batch', function (): void {
        $batch = Batch::factory()->create(['ssc_year' => 1999]);

        $post = Post::factory()
            ->forBatch($batch->id)
            ->create(['author_member_id' => Member::factory()->approved()->for($batch)->create()->id]);

        // 404, not 403. Telling somebody a batch post they cannot read exists
        // is itself a small disclosure.
        $this->actingAs(communityMember(Batch::factory()->create(['ssc_year' => 2001])))
            ->get("/community/{$post->ulid}")
            ->assertNotFound();
    });

    it('filters by category', function (): void {
        $author = Member::factory()->approved()->for(communityBatch())->create();

        Post::factory()->create([
            'author_member_id' => $author->id,
            'category' => PostCategory::Career,
        ]);
        Post::factory()->create([
            'author_member_id' => $author->id,
            'category' => PostCategory::Memories,
        ]);

        $this->actingAs(communityMember())
            ->get('/community?category=career')
            ->assertInertia(fn ($page) => $page->has('posts.data', 1));
    });
});

describe('posting', function (): void {
    it('records a post against the author', function (): void {
        $user = communityMember();

        $this->actingAs($user)
            ->post('/community', [
                'category' => PostCategory::General->value,
                'title' => 'Reunion photographs',
                'body' => 'Does anybody still have the 1998 sports day pictures?',
            ])
            ->assertRedirect();

        expect(Post::query()->where('author_member_id', $user->member->id)->exists())->toBeTrue();
    });

    it('sends a batch post to the author own batch and nowhere else', function (): void {
        $batch = Batch::factory()->create(['ssc_year' => 1999]);
        $user = communityMember($batch);

        $this->actingAs($user)->post('/community', [
            'category' => PostCategory::Batch->value,
            'body' => 'Who is coming to the meetup?',
        ]);

        // There is no field for choosing a batch: posting into a cohort you
        // did not attend is not a thing this application does.
        expect(Post::query()->first()?->batch_id)->toBe($batch->id);
    });

    it('lets the author edit their own post and nobody else edit it', function (): void {
        $author = communityMember();
        $stranger = communityMember();

        $post = Post::factory()->create(['author_member_id' => $author->member->id]);

        $this->actingAs($stranger)
            ->put("/community/{$post->ulid}", ['body' => 'Rewritten by somebody else'])
            ->assertForbidden();

        $this->actingAs($author)
            ->put("/community/{$post->ulid}", ['body' => 'Rewritten by me'])
            ->assertRedirect();

        expect($post->fresh()?->body)->toBe('Rewritten by me');
    });

    it('will not let a moderator rewrite a member words', function (): void {
        $moderator = User::factory()->create();
        $moderator->syncRoles(['Moderator']);
        Member::factory()->approved()->for(communityBatch())->create(['user_id' => $moderator->id]);

        $post = Post::factory()->create([
            'author_member_id' => Member::factory()->approved()->for(communityBatch())->create()->id,
            'body' => 'The original words',
        ]);

        // Moderators hide and remove. Nothing in this application publishes
        // different words under somebody else's name.
        $this->actingAs($moderator)
            ->put("/community/{$post->ulid}", ['body' => 'Something else entirely'])
            ->assertForbidden();

        expect($post->fresh()?->body)->toBe('The original words');
    });
});

describe('comments', function (): void {
    it('adds a comment and counts it on the post', function (): void {
        $post = Post::factory()->create([
            'author_member_id' => Member::factory()->approved()->for(communityBatch())->create()->id,
        ]);

        $this->actingAs(communityMember())
            ->post("/community/{$post->ulid}/comments", ['body' => 'I have those photographs.'])
            ->assertRedirect();

        expect($post->fresh()?->comments_count)->toBe(1)
            ->and($post->fresh()?->last_activity_at)->not->toBeNull();
    });

    it('flattens a reply to a reply onto the same parent', function (): void {
        $post = Post::factory()->create([
            'author_member_id' => Member::factory()->approved()->for(communityBatch())->create()->id,
        ]);

        $top = Comment::factory()->create([
            'commentable_type' => $post->getMorphClass(),
            'commentable_id' => $post->id,
            'parent_id' => null,
        ]);

        $reply = Comment::factory()->create([
            'commentable_type' => $post->getMorphClass(),
            'commentable_id' => $post->id,
            'parent_id' => $top->id,
        ]);

        $this->actingAs(communityMember())->post("/community/{$post->ulid}/comments", [
            'body' => 'Answering the reply',
            'parent_id' => $reply->id,
        ]);

        // One level deep. A thread that nests forever is unreadable on the
        // phone most of this association reads it on.
        expect(Comment::query()->latest('id')->first()?->parent_id)->toBe($top->id);
    });

    it('refuses a comment on a closed thread', function (): void {
        $post = Post::factory()->create([
            'author_member_id' => Member::factory()->approved()->for(communityBatch())->create()->id,
            'comments_enabled' => false,
        ]);

        $this->actingAs(communityMember())
            ->post("/community/{$post->ulid}/comments", ['body' => 'One more thing'])
            ->assertForbidden();
    });

    it('lets the post author remove a comment on their own post', function (): void {
        $author = communityMember();

        $post = Post::factory()->create(['author_member_id' => $author->member->id]);

        $comment = Comment::factory()->create([
            'commentable_type' => $post->getMorphClass(),
            'commentable_id' => $post->id,
        ]);

        // Somebody who starts a thread is responsible for it. Waiting on a
        // moderator to remove an insult under your own post is not reasonable.
        $this->actingAs($author)
            ->delete("/community/comments/{$comment->id}")
            ->assertRedirect();

        // Soft-deleted, so a report already filed against it still points at
        // something a moderator can read.
        $this->assertSoftDeleted($comment);

        expect($post->fresh()?->comments_count)->toBe(0);
    });
});
