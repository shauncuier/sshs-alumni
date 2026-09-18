<?php

declare(strict_types=1);

use App\Enums\ContentStatus;
use App\Enums\StoryStatus;
use App\Models\AlumniStory;
use App\Models\Batch;
use App\Models\GalleryAlbum;
use App\Models\Member;
use App\Models\News;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * The publishing rule, from both sides.
 *
 * A DRAFT DOES NOT EXIST as far as the public is concerned — a 404, never a
 * 403. "Forbidden" tells a stranger that an article they cannot read is being
 * written, which matters when the draft is an announcement about a death or a
 * committee decision not yet taken.
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function contentManager(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Content Manager']);

    return $user;
}

describe('news', function (): void {
    it('shows a published article and 404s a draft', function (): void {
        $published = News::factory()->create([
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $draft = News::factory()->create([
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ]);

        $this->get("/news/{$published->slug}")->assertOk();
        $this->get("/news/{$draft->slug}")->assertNotFound();
    });

    it('keeps an article scheduled for the future out of the listing', function (): void {
        News::factory()->create([
            'status' => ContentStatus::Published,
            'published_at' => now()->addWeek(),
        ]);

        // Published on a date is not published NOW. Getting this wrong puts
        // Friday's announcement out on Tuesday, and search engines will have
        // had it before anybody notices.
        $this->get('/news')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('news.data', 0));
    });

    it('counts a read without touching the article', function (): void {
        $article = News::factory()->create([
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
            'views_count' => 0,
        ]);

        $before = $article->updated_at;

        $this->get("/news/{$article->slug}");

        $fresh = $article->fresh();

        expect($fresh?->views_count)->toBe(1)
            // Reading an article is not editing it.
            ->and($fresh?->updated_at?->toIso8601String())
            ->toBe($before?->toIso8601String());
    });
});

describe('the publish permission', function (): void {
    it('lets a content manager write a draft', function (): void {
        $this->actingAs(contentManager())
            ->post('/admin/news', [
                'title' => 'The 2026 reunion committee has been formed',
                'body' => 'The committee met for the first time on Friday.',
            ])
            ->assertRedirect();

        // Written is not published.
        expect(News::query()->first()?->status)->toBe(ContentStatus::Draft);
    });

    it('is a separate permission from writing', function (): void {
        $writer = User::factory()->create();
        $writer->syncRoles(['Batch Coordinator']);

        $article = News::factory()->create(['status' => ContentStatus::Draft]);

        // A batch coordinator can draft an item about their own cohort
        // without being able to put it on the front page.
        $this->actingAs($writer)
            ->put("/admin/news/{$article->id}/publish", ['publish' => true])
            ->assertForbidden();

        expect($article->fresh()?->status)->toBe(ContentStatus::Draft);
    });

    it('keeps the first publication date when an article is republished', function (): void {
        $first = now()->subYear();

        $article = News::factory()->create([
            'status' => ContentStatus::Published,
            'published_at' => $first,
        ]);

        $manager = contentManager();

        $this->actingAs($manager)->put("/admin/news/{$article->id}/publish", ['publish' => false]);
        $this->actingAs($manager)->put("/admin/news/{$article->id}/publish", ['publish' => true]);

        // Correcting a typo in an old article must not republish it to the
        // top of a list ordered by publication date.
        expect($article->fresh()?->published_at?->toDateString())
            ->toBe($first->toDateString());
    });
});

describe('system pages', function (): void {
    it('refuses to delete the privacy policy', function (): void {
        $page = Page::factory()->create([
            'slug' => 'privacy-policy',
            'is_system' => true,
        ]);

        // Enforced at the endpoint, not only hidden in the UI. A deleted
        // privacy policy is a legal problem rather than a content one.
        $this->actingAs(contentManager())
            ->delete("/admin/pages/{$page->id}")
            ->assertForbidden();

        expect($page->fresh())->not->toBeNull();
    });

    it('deletes an ordinary page', function (): void {
        $page = Page::factory()->create(['is_system' => false]);

        $this->actingAs(contentManager())
            ->delete("/admin/pages/{$page->id}")
            ->assertRedirect();

        $this->assertSoftDeleted($page);
    });

    it('404s a draft page', function (): void {
        $page = Page::factory()->create([
            'slug' => 'terms',
            'status' => ContentStatus::Draft,
        ]);

        $this->get('/p/terms')->assertNotFound();

        $page->publish();

        $this->get('/p/terms')->assertOk();
    });
});

describe('alumni stories', function (): void {
    it('lands a submission as pending, bylined to the member', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);

        $member = Member::factory()->approved()->for(Batch::factory())->create([
            'user_id' => $user->id,
            'full_name' => 'Kamrul Hasan',
        ]);

        $this->actingAs($user)
            ->post('/my/stories', [
                'title' => 'From Sitakunda to Singapore',
                'body' => str_repeat('This is the story of what happened after school. ', 10),
            ])
            ->assertRedirect();

        $story = AlumniStory::query()->first();

        expect($story?->status)->toBe(StoryStatus::Pending)
            // The name the association knows them by, not a field they type.
            ->and($story?->author_name)->toBe('Kamrul Hasan')
            ->and($story?->member_id)->toBe($member->id);
    });

    it('keeps a pending story off the public site', function (): void {
        $story = AlumniStory::factory()->create(['status' => StoryStatus::Pending]);

        $this->get("/stories/{$story->slug}")->assertNotFound();

        $this->get('/stories')
            ->assertInertia(fn ($page) => $page->has('stories.data', 0));
    });

    it('keeps a rejected story and its text', function (): void {
        $story = AlumniStory::factory()->create([
            'status' => StoryStatus::Pending,
            'body' => 'The original account',
        ]);

        $this->actingAs(contentManager())
            ->put("/admin/stories/{$story->id}/review", ['status' => StoryStatus::Rejected->value])
            ->assertRedirect();

        // A member who asks why it was not published is owed an answer, and a
        // decision made in five minutes can be reversed in five minutes.
        $fresh = $story->fresh();

        expect($fresh)->not->toBeNull()
            ->and($fresh?->status)->toBe(StoryStatus::Rejected)
            ->and($fresh?->body)->toBe('The original account');
    });

    it('locks a published story against the author', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);

        $member = Member::factory()->approved()->for(Batch::factory())->create([
            'user_id' => $user->id,
        ]);

        $story = AlumniStory::factory()->create([
            'member_id' => $member->id,
            'status' => StoryStatus::Published,
        ]);

        // The version somebody reviewed and the version on the site have to be
        // the same one.
        $this->actingAs($user)
            ->put("/my/stories/{$story->id}", [
                'title' => 'Rewritten after publication',
                'body' => str_repeat('Different words entirely. ', 20),
            ])
            ->assertForbidden();
    });
});

describe('the gallery', function (): void {
    it('404s a draft album', function (): void {
        $album = GalleryAlbum::factory()->create(['status' => ContentStatus::Draft]);

        $this->get("/gallery/{$album->slug}")->assertNotFound();
    });

    it('shows a published one', function (): void {
        $album = GalleryAlbum::factory()->create([
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $this->get("/gallery/{$album->slug}")->assertOk();
    });
});
