<?php

declare(strict_types=1);

use App\Enums\AnnouncementKind;
use App\Enums\AudienceScope;
use App\Enums\ContentStatus;
use App\Enums\MemberStatus;
use App\Models\Announcement;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * The home page, and who sees which announcement.
 *
 * The audience rule is the one worth testing hard: an announcement addressed
 * to members must not appear on the front page a stranger reads, and a batch
 * announcement belongs to that batch.
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function liveAnnouncement(AudienceScope $audience, ?Batch $batch = null): Announcement
{
    return Announcement::factory()->create([
        'kind' => AnnouncementKind::Announcement,
        'audience' => $audience,
        'batch_id' => $batch?->id,
        'status' => ContentStatus::Published,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addWeek(),
    ]);
}

describe('the home page', function (): void {
    it('renders for a stranger', function (): void {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/home')
                ->has('stats.members')
                ->has('stats.batches')
                ->has('stats.events')
                ->has('stats.years')
            );
    });

    it('counts only approved members', function (): void {
        $batch = Batch::factory()->create();

        Member::factory()->approved()->for($batch)->count(2)->create();
        Member::factory()->for($batch)->create(['status' => MemberStatus::Pending]);

        $this->get('/')
            ->assertInertia(fn ($page) => $page->where('stats.members', 2));
    });

    it('counts only batches that have members', function (): void {
        $withMembers = Batch::factory()->create(['ssc_year' => 1999, 'members_count' => 4]);
        Batch::factory()->create(['ssc_year' => 2001, 'members_count' => 0]);

        // Every SSC year exists as a row whether or not anybody from it has
        // joined; counting those would claim a reach the association does not
        // have.
        expect($withMembers->members_count)->toBe(4);

        $this->get('/')
            ->assertInertia(fn ($page) => $page->where('stats.batches', 1));
    });
});

describe('announcement audiences', function (): void {
    it('shows a stranger the public ones only', function (): void {
        liveAnnouncement(AudienceScope::Public);
        liveAnnouncement(AudienceScope::Members);

        $this->get('/announcements')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('announcements.data', 1));
    });

    it('shows an approved member the members ones too', function (): void {
        liveAnnouncement(AudienceScope::Public);
        liveAnnouncement(AudienceScope::Members);

        $user = User::factory()->create();
        $user->syncRoles(['Member']);
        Member::factory()->approved()->for(Batch::factory())->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/announcements')
            ->assertInertia(fn ($page) => $page->has('announcements.data', 2));
    });

    it('keeps a batch announcement inside its batch', function (): void {
        $batch = Batch::factory()->create(['ssc_year' => 1999]);
        $other = Batch::factory()->create(['ssc_year' => 2001]);

        liveAnnouncement(AudienceScope::Batch, $batch);

        $inside = User::factory()->create();
        $inside->syncRoles(['Member']);
        Member::factory()->approved()->for($batch)->create(['user_id' => $inside->id]);

        $outside = User::factory()->create();
        $outside->syncRoles(['Member']);
        Member::factory()->approved()->for($other)->create(['user_id' => $outside->id]);

        $this->actingAs($inside)
            ->get('/announcements')
            ->assertInertia(fn ($page) => $page->has('announcements.data', 1));

        $this->actingAs($outside)
            ->get('/announcements')
            ->assertInertia(fn ($page) => $page->has('announcements.data', 0));
    });

    it('takes an expired announcement down on its own', function (): void {
        Announcement::factory()->create([
            'audience' => AudienceScope::Public,
            'status' => ContentStatus::Published,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);

        // "Registration closes on Friday" is worse than useless on Saturday,
        // and expecting somebody to remember to take it down is expecting the
        // wrong thing.
        $this->get('/announcements')
            ->assertInertia(fn ($page) => $page->has('announcements.data', 0));
    });

    it('holds back one whose window has not opened', function (): void {
        Announcement::factory()->create([
            'audience' => AudienceScope::Public,
            'status' => ContentStatus::Published,
            'starts_at' => now()->addWeek(),
            'ends_at' => null,
        ]);

        $this->get('/announcements')
            ->assertInertia(fn ($page) => $page->has('announcements.data', 0));
    });

    it('drops the batch id when the audience is not a batch', function (): void {
        $batch = Batch::factory()->create();
        $manager = User::factory()->create();
        $manager->syncRoles(['Content Manager']);

        $this->actingAs($manager)->post('/admin/announcements', [
            'kind' => AnnouncementKind::Notice->value,
            'title' => 'Office closed on Thursday',
            'body' => 'The association office is closed for the holiday.',
            'level' => 'info',
            'audience' => AudienceScope::Public->value,
            'batch_id' => $batch->id,
        ]);

        // A batch id on an association-wide notice is noise that reads as a
        // rule.
        expect(Announcement::query()->first()?->batch_id)->toBeNull();
    });
});
