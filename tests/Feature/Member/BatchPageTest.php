<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * A member's own cohort page.
 *
 * `show_in_batch_list` is an ADDITIONAL opt-out on top of `show_profile`, so
 * these tests check both doors independently. The count must stay honest even
 * when the list is shorter than it.
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * An approved member of the given batch, signed in.
 */
function batchMember(Batch $batch): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    Member::factory()->approved()->for($batch)->create(['user_id' => $user->id]);

    return $user;
}

describe('access', function (): void {
    it('is closed to a guest', function (): void {
        $this->get('/my/batch')->assertRedirect('/login');
    });

    it('is closed to a member whose application is still pending', function (): void {
        $batch = Batch::factory()->create();

        $user = User::factory()->create();
        $user->syncRoles(['Member']);
        Member::factory()->for($batch)->create([
            'user_id' => $user->id,
            'status' => MemberStatus::Pending,
        ]);

        // EnsureMemberApproved redirects with an explanation rather than
        // throwing a bare 403.
        $this->actingAs($user)->get('/my/batch')->assertRedirect();
    });
});

describe('a member with no batch', function (): void {
    it('is told how to get one instead of seeing an error', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);
        Member::factory()->approved()->create([
            'user_id' => $user->id,
            'batch_id' => null,
        ]);

        $this->actingAs($user)
            ->get('/my/batch')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('member/batch')
                ->where('batch', null)
                ->where('members', null)
            );
    });
});

describe('the batch roll', function (): void {
    it('lists batchmates who are visible', function (): void {
        $batch = Batch::factory()->create(['ssc_year' => 1994]);
        $user = batchMember($batch);

        Member::factory()->approved()->count(2)->for($batch)->create();

        $this->actingAs($user)
            ->get('/my/batch')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('member/batch')
                ->where('batch.ssc_year', 1994)
                ->has('members.data', 3)
                // The Pagination component reads its page links from `meta`.
                ->has('members.meta.links')
            );
    });

    it('excludes a member of another batch', function (): void {
        $batch = Batch::factory()->create(['ssc_year' => 1994]);
        $other = Batch::factory()->create(['ssc_year' => 1995]);

        $user = batchMember($batch);
        Member::factory()->approved()->for($other)->create();

        $this->actingAs($user)
            ->get('/my/batch')
            ->assertInertia(fn ($page) => $page->has('members.data', 1));
    });

    it('honours show_in_batch_list without touching the count', function (): void {
        $batch = Batch::factory()->create();
        $user = batchMember($batch);

        $hidden = Member::factory()->approved()->for($batch)->create();
        $hidden->privacy()->update(['show_in_batch_list' => false]);

        $this->actingAs($user)
            ->get('/my/batch')
            ->assertInertia(fn ($page) => $page
                // The hidden member is absent from the list …
                ->has('members.data', 1)
                // … but still counted, because they are still in the cohort.
                ->where('batch.members_count', 2)
            );
    });

    it('excludes a member who has hidden their profile entirely', function (): void {
        $batch = Batch::factory()->create();
        $user = batchMember($batch);

        $hidden = Member::factory()->approved()->for($batch)->create();
        $hidden->privacy()->update(['show_profile' => false]);

        $this->actingAs($user)
            ->get('/my/batch')
            ->assertInertia(fn ($page) => $page->has('members.data', 1));
    });

    it('shows coordinators with a link to their directory profile', function (): void {
        $batch = Batch::factory()->create();
        $user = batchMember($batch);

        $coordinator = Member::factory()->approved()->for($batch)->create();
        $batch->coordinators()->attach($coordinator->id, ['assigned_at' => now()]);

        $this->actingAs($user)
            ->get('/my/batch')
            ->assertInertia(fn ($page) => $page
                ->has('coordinators', 1)
                ->where('coordinators.0.ulid', $coordinator->ulid)
            );
    });
});
