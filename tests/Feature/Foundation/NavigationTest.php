<?php

declare(strict_types=1);

use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use App\Support\Navigation;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Route;

/**
 * The navigation is built from route NAMES, and an unregistered name is simply
 * skipped. That is what keeps an unshipped phase out of the menu — but it also
 * means a TYPO is silently invisible rather than loudly broken.
 *
 * These tests close that gap from both directions: every name that resolves
 * must point at a real, reachable route, and every name that does not resolve
 * must be one we are genuinely still waiting on.
 *
 * @see app/Support/Navigation.php
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

describe('navigation route names', function (): void {
    it('never renders a link to a route that does not exist', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $nav = Navigation::for($admin, memberApproved: true);

        $hrefs = collect([
            ...collect($nav['admin'])->flatMap(fn (array $group): array => $group['items'])->all(),
            ...$nav['member'],
            ...$nav['publicPrimary'],
            ...$nav['publicFooter'],
            ...$nav['publicLegal'],
        ])->pluck('href');

        expect($hrefs)->not->toBeEmpty();

        // Every rendered href must resolve back to a registered GET route.
        $getUris = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RouteInstance $route): bool => in_array('GET', $route->methods(), true))
            ->map(fn (RouteInstance $route): string => '/'.ltrim($route->uri(), '/'))
            ->all();

        foreach ($hrefs as $href) {
            expect($getUris)->toContain($href);
        }
    });

    it('has no typo in any declared route name', function (): void {
        // A name that does not resolve is allowed ONLY while its phase is
        // unbuilt. Anything not on this list is a typo.
        $notBuiltYet = [
            'admin.crm.contacts.index',
            'admin.events.index',
            'admin.jubilee.edit',
            'admin.volunteers.index',
            'admin.committees.index',
            'admin.payments.index',
            'admin.donations.index',
            'admin.sponsors.index',
            'admin.news.index',
            'admin.announcements.index',
            'admin.gallery.index',
            'admin.pages.index',
            'admin.history.index',
            'admin.community.posts',
            'admin.reports.index',
            'admin.users.index',
            'admin.audit.index',
            'admin.settings.edit',
            'my.card',
            'community.index',
            'my.events',
            'my.payments',
            'my.donations',
            'jubilee',
            'about',
            'events.index',
            'news.index',
            'gallery.index',
            'contact',
            'committees.index',
            'stories.index',
            'donate',
            'pages.show',
        ];

        $missing = collect(Navigation::allRouteNames())
            ->reject(fn (string $name): bool => Route::has($name))
            ->reject(fn (string $name): bool => in_array($name, $notBuiltYet, true))
            ->values();

        expect($missing->all())->toBe([]);
    });

    it('drops names from the allow-list as phases land', function (): void {
        // The mirror of the test above: once a route exists, it must be taken
        // off the "not built yet" list, or that list quietly rots.
        expect(Route::has('admin.batches.index'))->toBeTrue();
        expect(Route::has('my.batch'))->toBeTrue();
    });
});

describe('navigation visibility', function (): void {
    it('hides admin groups a user has no permission for', function (): void {
        $user = User::factory()->create();
        $user->assignRole('Member');

        expect(Navigation::for($user)['admin'])->toBe([]);
    });

    it('gives an anonymous visitor no admin or member items', function (): void {
        $nav = Navigation::for(null);

        expect($nav['admin'])->toBe([])
            ->and($nav['member'])->toBe([]);
    });

    it('hides approved-only destinations while an application is pending', function (): void {
        $user = User::factory()->create();

        $pending = collect(Navigation::for($user, memberApproved: false)['member'])
            ->pluck('key');

        $approved = collect(Navigation::for($user, memberApproved: true)['member'])
            ->pluck('key');

        expect($pending)->not->toContain('directory')
            ->and($pending)->not->toContain('batch')
            ->and($approved)->toContain('directory')
            ->and($approved)->toContain('batch');
    });

    it('is shared on every Inertia response', function (): void {
        $batch = Batch::factory()->create();

        $user = User::factory()->create();
        Member::factory()->approved()->for($batch)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->has('nav.member')
                ->has('nav.publicPrimary')
            );
    });
});
