<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Models\Batch;
use App\Models\Member;
use App\Models\Post;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Notifications\MentionNotification;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

describe('in-app notification center', function (): void {
    it('shows member their notifications feed and unread count', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);

        $user->notify(new AppNotification(
            title: 'Welcome to the platform',
            body: 'Your profile has been created.',
        ));

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('member/notifications')
                ->has('notifications.data', 1)
                ->where('unreadCount', 1)
            );
    });

    it('marks a single notification as read', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);

        $user->notify(new AppNotification(
            title: 'Fee reminder',
            body: 'Annual membership fee is due.',
        ));

        $notification = $user->unreadNotifications()->first();
        expect($notification)->not->toBeNull();

        $this->actingAs($user)
            ->post("/notifications/{$notification->id}/read")
            ->assertRedirect();

        expect($notification->fresh()->read_at)->not->toBeNull();
        expect($user->unreadNotifications()->count())->toBe(0);
    });

    it('marks all notifications as read in bulk', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);

        $user->notify(new AppNotification(title: 'Notice 1', body: 'Body 1'));
        $user->notify(new AppNotification(title: 'Notice 2', body: 'Body 2'));

        expect($user->unreadNotifications()->count())->toBe(2);

        $this->actingAs($user)
            ->post('/notifications/read-all')
            ->assertRedirect();

        expect($user->unreadNotifications()->count())->toBe(0);
    });

    it('creates a mention notification for an alumnus', function (): void {
        $batch = Batch::factory()->create(['ssc_year' => 2010]);

        $authorUser = User::factory()->create();
        $authorMember = Member::factory()->approved()->for($batch)->create(['user_id' => $authorUser->id, 'full_name' => 'Fahim Ahmed']);

        $mentionedUser = User::factory()->create();
        $mentionedMember = Member::factory()->approved()->for($batch)->create(['user_id' => $mentionedUser->id, 'full_name' => 'Tanvir Hossain']);

        $post = Post::factory()->create([
            'author_member_id' => $authorMember->id,
            'title' => 'Reunion Planning',
        ]);

        $notification = new MentionNotification($authorMember, $post);
        $mentionedUser->notify($notification);

        expect($mentionedUser->notifications()->count())->toBe(1);

        $stored = $mentionedUser->notifications()->first();
        expect($stored->data['title'])->toContain('Fahim Ahmed mentioned you')
            ->and($stored->data['category'])->toBe('community');
    });
});
