<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberPrivacy;
use App\Models\Post;
use App\Models\User;
use App\Services\Community\MentionParser;
use Database\Seeders\RolePermissionSeeder;

/**
 * Mentions, and where the privacy line falls in the community.
 *
 * Posting is a public act inside the community — you cannot write under a name
 * nobody may see — so the NAME is always present. What a member controls is
 * their face beside their words and whether their name is a door into a
 * profile they closed.
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function mentionBatch(): Batch
{
    return Batch::query()->first() ?? Batch::factory()->create();
}

function mentionMember(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Member']);
    Member::factory()->approved()->for(mentionBatch())->create(['user_id' => $user->id]);

    return $user;
}

describe('the parser', function (): void {
    it('resolves a mention to a name', function (): void {
        $mentioned = Member::factory()->approved()->for(mentionBatch())->create(['full_name' => 'Rafiqul Islam']);

        $resolved = app(MentionParser::class)
            ->resolveMany(["Ask @member:{$mentioned->ulid} about it"]);

        expect($resolved[$mentioned->ulid]['name'])->toBe('Rafiqul Islam');
    });

    it('names a member who has hidden their profile but does not link them', function (): void {
        $mentioned = Member::factory()->approved()->for(mentionBatch())->create(['full_name' => 'Nasrin Akter']);
        $mentioned->privacy->update(['show_profile' => false]);

        $resolved = app(MentionParser::class)
            ->resolveMany(["Thanks @member:{$mentioned->ulid}"]);

        expect($resolved[$mentioned->ulid]['name'])->toBe('Nasrin Akter')
            ->and($resolved[$mentioned->ulid]['url'])->toBeNull();
    });

    it('resolves nothing for a member who is not approved', function (): void {
        $pending = Member::factory()->for(mentionBatch())->create([
            'status' => MemberStatus::Pending,
        ]);

        $resolved = app(MentionParser::class)->resolveMany(["Hello @member:{$pending->ulid}"]);

        // An unresolvable token renders as the literal text the author typed.
        // That is the honest failure: it says somebody was mentioned without
        // inventing who.
        expect($resolved)->toBe([]);
    });

    it('resolves every mention across many bodies in one pass', function (): void {
        $first = Member::factory()->approved()->for(mentionBatch())->create();
        $second = Member::factory()->approved()->for(mentionBatch())->create();

        $resolved = app(MentionParser::class)->resolveMany([
            "@member:{$first->ulid} and @member:{$second->ulid}",
            "@member:{$first->ulid} again",
        ]);

        expect($resolved)->toHaveCount(2);
    });
});

describe('the author payload', function (): void {
    it('omits the photo and the link for a hidden author', function (): void {
        $author = Member::factory()->approved()->for(mentionBatch())->create([
            'photo_path' => 'profile/2026/09/example.jpg',
        ]);
        $author->privacy->update(['show_profile' => false]);

        $post = Post::factory()->create(['author_member_id' => $author->id]);

        $this->actingAs(mentionMember())
            ->get("/community/{$post->ulid}")
            ->assertInertia(fn ($page) => $page
                // The name is there. The face and the door are not — and they
                // are ABSENT, not null, in keeping with the rest of the
                // resource layer.
                ->where('post.author.name', $author->full_name)
                ->missing('post.author.photo_url')
                ->missing('post.author.url')
            );
    });

    it('includes them for an author who shows their profile', function (): void {
        $author = Member::factory()->approved()->for(mentionBatch())->create();

        expect($author->privacy)->toBeInstanceOf(MemberPrivacy::class);

        $post = Post::factory()->create(['author_member_id' => $author->id]);

        $this->actingAs(mentionMember())
            ->get("/community/{$post->ulid}")
            ->assertInertia(fn ($page) => $page->has('post.author.url'));
    });
});
