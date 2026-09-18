<?php

declare(strict_types=1);

use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use App\Support\Navigation;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        // Every rendered href must actually ROUTE.
        //
        // Not "must appear in the list of route URIs" — the legal pages
        // resolve to `/p/privacy-policy` through a route declared as
        // `p/{page:slug}`, and a string comparison cannot see that those are
        // the same thing. Asking the router is both simpler and stricter.
        foreach ($hrefs as $href) {
            $routed = true;

            try {
                Route::getRoutes()->match(Request::create($href, 'GET'));
            } catch (NotFoundHttpException) {
                $routed = false;
            }

            expect($routed)->toBeTrue("Navigation renders {$href}, which does not route.");
        }
    });

    it('has no typo in any declared route name', function (): void {
        // A name that does not resolve is allowed ONLY while its phase is
        // unbuilt. Anything not on this list is a typo.
        $notBuiltYet = [
            'admin.reports.index',
            'admin.users.index',
            'admin.audit.index',
            'admin.settings.edit',
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
        expect(Route::has('admin.events.index'))->toBeTrue();
        expect(Route::has('events.index'))->toBeTrue();
        expect(Route::has('jubilee'))->toBeTrue();
        expect(Route::has('my.events'))->toBeTrue();
        expect(Route::has('my.card'))->toBeTrue();
        expect(Route::has('admin.crm.contacts.index'))->toBeTrue();
        expect(Route::has('admin.crm.pipeline'))->toBeTrue();
        expect(Route::has('admin.payments.index'))->toBeTrue();
        expect(Route::has('admin.volunteers.index'))->toBeTrue();
        expect(Route::has('committees.index'))->toBeTrue();
        expect(Route::has('donate'))->toBeTrue();

        // Phase 6.
        expect(Route::has('community.index'))->toBeTrue();
        expect(Route::has('admin.community.posts'))->toBeTrue();

        // Phase 7.
        expect(Route::has('home'))->toBeTrue();
        expect(Route::has('about'))->toBeTrue();
        expect(Route::has('news.index'))->toBeTrue();
        expect(Route::has('gallery.index'))->toBeTrue();
        expect(Route::has('stories.index'))->toBeTrue();
        expect(Route::has('contact'))->toBeTrue();
        expect(Route::has('pages.show'))->toBeTrue();
        expect(Route::has('admin.news.index'))->toBeTrue();
        expect(Route::has('admin.announcements.index'))->toBeTrue();
        expect(Route::has('admin.gallery.index'))->toBeTrue();
        expect(Route::has('admin.pages.index'))->toBeTrue();
        expect(Route::has('admin.history.index'))->toBeTrue();
        expect(Route::has('admin.stories.index'))->toBeTrue();
        expect(Route::has('admin.faqs.index'))->toBeTrue();
        expect(Route::has('admin.media.index'))->toBeTrue();
        expect(Route::has('my.stories'))->toBeTrue();
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
