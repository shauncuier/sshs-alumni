<?php

declare(strict_types=1);

use App\Models\Batch;
use App\Models\CrmContact;
use App\Models\Donation;
use App\Models\Event;
use App\Models\Member;
use App\Models\MembershipFee;
use App\Models\Sponsor;
use App\Models\User;
use App\Models\Volunteer;
use Database\Seeders\RolePermissionSeeder;

/**
 * THE PAGINATION SHAPE.
 *
 * A bare paginator serialises its page numbers at the TOP level:
 * `{data, current_page, last_page, links, …}`. An API Resource collection
 * nests them under `meta`. The Pagination component reads `meta`, so a
 * controller returning `$query->paginate()->through(...)` renders a page that
 * throws on `meta.last_page` and shows nothing at all.
 *
 * This has now shipped twice, in two different phases, because the broken
 * shape is invisible to a `->has('rows.data', 3)` assertion — the data is
 * there, only the envelope is wrong.
 *
 * So this walks every paginated admin and member page and asserts the
 * envelope. It is a shape test, not a behaviour test, and that is the point.
 *
 * @see app/Support/Paginated.php
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function superAdmin(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Super Admin']);

    return $user;
}

/**
 * Every paginated page, and the prop that carries the list.
 *
 * @return array<int, array{0: string, 1: string}>
 */
dataset('paginated pages', [
    'members' => ['/admin/members', 'members'],
    'batches' => ['/admin/batches', 'batches'],
    'events' => ['/admin/events', 'events'],
    'crm contacts' => ['/admin/crm/contacts', 'contacts'],
    'crm tasks' => ['/admin/crm/tasks', 'tasks'],
    'payments' => ['/admin/payments', 'payments'],
    'fees' => ['/admin/fees', 'fees'],
    'donations' => ['/admin/donations', 'donations'],
    'sponsors' => ['/admin/sponsors', 'sponsors'],
    'volunteers' => ['/admin/volunteers', 'volunteers'],
]);

it('gives every paginated admin page a meta envelope', function (string $url, string $prop): void {
    // One row each, so the lists are not empty and `links` is populated.
    $member = Member::factory()->approved()->for(Batch::factory())->create();
    MembershipFee::factory()->create(['member_id' => $member->id]);
    Event::factory()->create();
    CrmContact::factory()->create();
    Donation::factory()->create();
    Sponsor::factory()->create();
    Volunteer::factory()->create();

    $this->actingAs(superAdmin())
        ->get($url)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has("{$prop}.data")
            // The three the component actually reads. `links` is the one that
            // threw both times.
            ->has("{$prop}.meta.last_page")
            ->has("{$prop}.meta.total")
            ->has("{$prop}.meta.links")
        );
})->with('paginated pages');

it('gives the member pages the same envelope', function (): void {
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    $member = Member::factory()->approved()->for(Batch::factory())->create([
        'user_id' => $user->id,
    ]);

    Donation::factory()->create(['donor_member_id' => $member->id]);

    foreach ([['/my/payments', 'payments'], ['/my/donations', 'donations']] as [$url, $prop]) {
        $this->actingAs($user)
            ->get($url)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has("{$prop}.data")
                ->has("{$prop}.meta.last_page")
                ->has("{$prop}.meta.links")
            );
    }
});

it('gives the directory the same envelope', function (): void {
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    Member::factory()->approved()->for(Batch::factory())->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get('/directory')
        ->assertInertia(fn ($page) => $page
            ->has('members.meta.last_page')
            ->has('members.meta.links')
        );
});

it('has no controller left returning a bare paginator', function (): void {
    // `->through()` keeps a paginator a paginator. Either use a Resource
    // collection or App\Support\Paginated::from().
    $offenders = [];

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(app_path('Http/Controllers')),
    );

    foreach ($files as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = (string) file_get_contents($file->getPathname());

        if (str_contains($contents, '->through(')) {
            $offenders[] = $file->getFilename();
        }
    }

    expect($offenders)->toBe([]);
});
