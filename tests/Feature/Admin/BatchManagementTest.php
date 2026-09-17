<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * Batch administration and coordinator assignment.
 *
 * The interesting assertions are the negative ones: a coordinator must belong
 * to the batch they coordinate, and a Batch Coordinator must not be able to
 * edit a cohort that is not theirs.
 *
 * @see docs/05-modules.md section 2
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function batchAdmin(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Membership Manager']);

    return $user;
}

describe('batch list', function (): void {
    it('is closed to an ordinary member, who holds batches.view for the public pages', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);

        $this->actingAs($user)->get('/admin/batches')->assertForbidden();
    });

    it('lists batches with their cached member count', function (): void {
        $batch = Batch::factory()->create(['ssc_year' => 1994, 'name' => 'SSC 1994']);
        Member::factory()->approved()->count(3)->for($batch)->create();

        $this->actingAs(batchAdmin())
            ->get('/admin/batches')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/batches/index')
                ->where('batches.data.0.ssc_year', 1994)
                ->where('batches.data.0.members_count', 3)
                // The Pagination component reads `meta`. A bare paginator puts
                // its page numbers at the top level instead and the component
                // throws, which no data assertion would notice.
                ->has('batches.meta.last_page')
                ->has('batches.links')
            );
    });

    it('finds a batch by SSC year', function (): void {
        Batch::factory()->create(['ssc_year' => 1994, 'name' => 'SSC 1994']);
        Batch::factory()->create(['ssc_year' => 2005, 'name' => 'SSC 2005']);

        $this->actingAs(batchAdmin())
            ->get('/admin/batches?q=2005')
            ->assertInertia(fn ($page) => $page
                ->has('batches.data', 1)
                ->where('batches.data.0.ssc_year', 2005)
            );
    });
});

describe('creating a batch', function (): void {
    it('derives the slug from the SSC year', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Membership Manager']);

        $this->actingAs($user)
            ->post('/admin/batches', [
                'name' => 'SSC 2001',
                'name_bn' => null,
                'ssc_year' => 2001,
                'description' => null,
                'description_bn' => null,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('batches', [
            'ssc_year' => 2001,
            'slug' => 'ssc-2001',
        ]);
    });

    it('refuses a second batch for the same SSC year', function (): void {
        Batch::factory()->create(['ssc_year' => 2001]);

        $this->actingAs(batchAdmin())
            ->post('/admin/batches', [
                'name' => 'Duplicate',
                'ssc_year' => 2001,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('ssc_year');
    });

    it('refuses a year before the school could have had an SSC cohort', function (): void {
        $this->actingAs(batchAdmin())
            ->post('/admin/batches', [
                'name' => 'Impossible',
                'ssc_year' => 1970,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('ssc_year');
    });
});

describe('coordinator assignment', function (): void {
    it('assigns an approved member of the same batch', function (): void {
        $batch = Batch::factory()->create();
        $member = Member::factory()->approved()->for($batch)->create();

        $this->actingAs(batchAdmin())
            ->post("/admin/batches/{$batch->id}/coordinators", [
                'member_id' => $member->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('batch_coordinators', [
            'batch_id' => $batch->id,
            'member_id' => $member->id,
        ]);
    });

    it('refuses a member from a different batch', function (): void {
        $batch = Batch::factory()->create(['ssc_year' => 1994]);
        $other = Batch::factory()->create(['ssc_year' => 1995]);
        $outsider = Member::factory()->approved()->for($other)->create();

        $this->actingAs(batchAdmin())
            ->post("/admin/batches/{$batch->id}/coordinators", [
                'member_id' => $outsider->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('batch_coordinators', 0);
    });

    it('refuses a member who is not approved', function (): void {
        $batch = Batch::factory()->create();
        $pending = Member::factory()->for($batch)->create([
            'status' => MemberStatus::Pending,
        ]);

        $this->actingAs(batchAdmin())
            ->post("/admin/batches/{$batch->id}/coordinators", [
                'member_id' => $pending->id,
            ])
            ->assertNotFound();
    });

    it('is idempotent, so a double submit is not an error', function (): void {
        $batch = Batch::factory()->create();
        $member = Member::factory()->approved()->for($batch)->create();
        $admin = batchAdmin();

        foreach (range(1, 2) as $ignored) {
            $this->actingAs($admin)
                ->post("/admin/batches/{$batch->id}/coordinators", [
                    'member_id' => $member->id,
                ])
                ->assertRedirect();
        }

        $this->assertDatabaseCount('batch_coordinators', 1);
    });

    it('removes a coordinator by ULID', function (): void {
        $batch = Batch::factory()->create();
        $member = Member::factory()->approved()->for($batch)->create();
        $batch->coordinators()->attach($member->id, ['assigned_at' => now()]);

        $this->actingAs(batchAdmin())
            ->delete("/admin/batches/{$batch->id}/coordinators/{$member->ulid}")
            ->assertRedirect();

        $this->assertDatabaseCount('batch_coordinators', 0);
    });

    it('omits already-assigned coordinators from the candidate list', function (): void {
        $batch = Batch::factory()->create();
        $assigned = Member::factory()->approved()->for($batch)->create();
        Member::factory()->approved()->for($batch)->create();

        $batch->coordinators()->attach($assigned->id, ['assigned_at' => now()]);

        $this->actingAs(batchAdmin())
            ->get("/admin/batches/{$batch->id}")
            ->assertInertia(fn ($page) => $page
                ->component('admin/batches/show')
                ->has('candidates', 1)
                ->has('batch.coordinators', 1)
            );
    });
});

describe('batch coordinator reach', function (): void {
    it('cannot edit a batch it does not coordinate', function (): void {
        $own = Batch::factory()->create(['ssc_year' => 1994]);
        $other = Batch::factory()->create(['ssc_year' => 1995]);

        $user = User::factory()->create();
        $user->syncRoles(['Batch Coordinator']);

        $member = Member::factory()->approved()->for($own)->create([
            'user_id' => $user->id,
        ]);
        $own->coordinators()->attach($member->id, ['assigned_at' => now()]);

        $this->actingAs($user)
            ->put("/admin/batches/{$other->id}", [
                'name' => 'Hijacked',
                'ssc_year' => 1995,
                'status' => 'active',
            ])
            ->assertForbidden();
    });

    it('can edit its own batch', function (): void {
        $batch = Batch::factory()->create(['ssc_year' => 1994]);

        $user = User::factory()->create();
        $user->syncRoles(['Batch Coordinator']);

        $member = Member::factory()->approved()->for($batch)->create([
            'user_id' => $user->id,
        ]);
        $batch->coordinators()->attach($member->id, ['assigned_at' => now()]);

        $this->actingAs($user)
            ->put("/admin/batches/{$batch->id}", [
                'name' => 'SSC 1994 Renamed',
                'ssc_year' => 1994,
                'status' => 'active',
            ])
            ->assertRedirect();

        expect($batch->fresh()->name)->toBe('SSC 1994 Renamed');
    });

    it('rewrites the slug when the SSC year is corrected', function (): void {
        $batch = Batch::factory()->create(['ssc_year' => 1994, 'slug' => 'ssc-1994']);

        $this->actingAs(batchAdmin())
            ->put("/admin/batches/{$batch->id}", [
                'name' => 'SSC 1995',
                'ssc_year' => 1995,
                'status' => 'active',
            ])
            ->assertRedirect();

        expect($batch->fresh()->slug)->toBe('ssc-1995');
    });
});
