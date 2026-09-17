<?php

declare(strict_types=1);

use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Collection;

/**
 * Directory filtering.
 *
 * Every option offered by the UI is built from directory-visible members, so a
 * filter can never present a value that returns an empty page — these tests
 * pin that down alongside the filtering itself.
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function directoryUser(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    Member::factory()->approved()->create([
        'user_id' => $user->id,
        'country' => 'Bangladesh',
        'occupation' => 'Teacher',
        'city' => 'Chattogram',
    ]);

    return $user;
}

describe('filters', function (): void {
    it('narrows by country', function (): void {
        $user = directoryUser();

        Member::factory()->approved()->create(['country' => 'Canada']);

        $this->actingAs($user)
            ->get('/directory?country=Canada')
            ->assertInertia(fn ($page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.ulid', Member::where('country', 'Canada')->sole()->ulid)
            );
    });

    it('narrows by occupation', function (): void {
        $user = directoryUser();

        Member::factory()->approved()->create(['occupation' => 'Cardiologist']);

        $this->actingAs($user)
            ->get('/directory?occupation=Cardiologist')
            ->assertInertia(fn ($page) => $page->has('members.data', 1));
    });

    it('narrows by city', function (): void {
        $user = directoryUser();

        Member::factory()->approved()->create(['city' => 'Rajshahi']);

        $this->actingAs($user)
            ->get('/directory?city=Rajshahi')
            ->assertInertia(fn ($page) => $page->has('members.data', 1));
    });

    it('combines filters rather than widening', function (): void {
        $batch = Batch::factory()->create();
        $user = directoryUser();

        Member::factory()->approved()->for($batch)->create([
            'country' => 'Canada',
            'occupation' => 'Engineer',
        ]);
        Member::factory()->approved()->for($batch)->create([
            'country' => 'Canada',
            'occupation' => 'Teacher',
        ]);

        $this->actingAs($user)
            ->get('/directory?country=Canada&occupation=Engineer')
            ->assertInertia(fn ($page) => $page->has('members.data', 1));
    });
});

describe('filter options', function (): void {
    it('offers only values that directory-visible members actually hold', function (): void {
        $user = directoryUser();

        $hidden = Member::factory()->approved()->create(['country' => 'Narnia']);
        $hidden->privacy()->update(['show_profile' => false]);

        $this->actingAs($user)
            ->get('/directory')
            ->assertInertia(fn ($page) => $page
                // A country only one hidden member holds would produce an
                // empty result page if it were offered.
                ->where('options.countries', fn (Collection $countries): bool => ! $countries->contains('Narnia'))
                ->where('options.occupations', fn (Collection $occupations): bool => $occupations->contains('Teacher'))
            );
    });

    it('echoes every accepted filter back to the UI', function (): void {
        $this->actingAs(directoryUser())
            ->get('/directory?country=Bangladesh')
            ->assertInertia(fn ($page) => $page
                ->has('members.meta.links')
                ->where('filters.country', 'Bangladesh')
                ->has('filters.occupation')
                ->has('filters.city')
            );
    });
});
