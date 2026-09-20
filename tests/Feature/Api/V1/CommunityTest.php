<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\MemberStatus;
use App\Enums\PostCategory;
use App\Enums\ReactionType;
use App\Models\Batch;
use App\Models\Member;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('forbids unapproved members from community feed', function (): void {
    $user = User::factory()->create();
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'status' => MemberStatus::Pending,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.community.index'))
        ->assertForbidden();
});

it('allows approved members to view community feed and single post', function (): void {
    $batch = Batch::factory()->create(['name' => 'SSC 2005']);
    $user = User::factory()->create();
    $user->syncRoles(['Member']);
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'status' => MemberStatus::Approved,
        'batch_id' => $batch->id,
    ]);

    $post = Post::factory()->create([
        'author_member_id' => $member->id,
        'title' => 'First Community Post',
        'category' => PostCategory::General,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    // List posts
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.community.index'));

    $response->assertOk()
        ->assertJsonStructure(['posts' => ['data', 'links', 'meta']])
        ->assertJsonPath('posts.data.0.title', 'First Community Post');

    // Show single post
    $showResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.community.show', $post->ulid));

    $showResponse->assertOk()
        ->assertJsonPath('post.title', 'First Community Post')
        ->assertJsonStructure(['post', 'comments']);
});

it('allows approved members to create a post, comment, and react', function (): void {
    $batch = Batch::factory()->create(['name' => 'SSC 2008']);
    $user = User::factory()->create();
    $user->syncRoles(['Member']);
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'status' => MemberStatus::Approved,
        'batch_id' => $batch->id,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    // Create post
    $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.community.store'), [
            'category' => PostCategory::General->value,
            'title' => 'New API Post',
            'body' => 'This is a test post body from API.',
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('post.title', 'New API Post');

    $postUlid = $createResponse->json('post.ulid');

    // Add comment
    $commentResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.community.comment', $postUlid), [
            'body' => 'Great post!',
        ]);

    $commentResponse->assertCreated()
        ->assertJsonPath('comment.body', 'Great post!');

    // Toggle reaction
    $reactResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.community.react', $postUlid), [
            'type' => ReactionType::Love->value,
        ]);

    $reactResponse->assertOk()
        ->assertJsonPath('toggled', true)
        ->assertJsonPath('reaction', ReactionType::Love->value);
});
