<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

/**
 * The single most valuable test in the project.
 *
 * It walks the REAL route list rather than a hand-maintained fixture, so an
 * admin route added without a permission fails the suite automatically. Nobody
 * has to remember to update a list.
 *
 * @see docs/08-security-privacy.md section 12
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * Every GET route under /admin.
 *
 * @return array<int, array{0: string, 1: string}>
 */
function adminGetRoutes(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RouteInstance $route): bool => str_starts_with($route->uri(), 'admin')
            && in_array('GET', $route->methods(), true))
        // Routes with bound parameters need a real model, which belongs in a
        // targeted test rather than this sweep.
        ->reject(fn (RouteInstance $route): bool => str_contains($route->uri(), '{'))
        ->map(fn (RouteInstance $route): array => [$route->uri(), (string) $route->getName()])
        ->values()
        ->all();
}

/**
 * The `can:` permission guarding a route, if any.
 */
function permissionFor(RouteInstance $route): ?string
{
    foreach ($route->gatherMiddleware() as $middleware) {
        if (is_string($middleware) && str_starts_with($middleware, 'can:')) {
            return substr($middleware, 4);
        }
    }

    return null;
}

describe('every admin route is protected', function (): void {
    it('declares a can: permission', function (): void {
        $unprotected = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RouteInstance $route): bool => str_starts_with($route->uri(), 'admin'))
            ->reject(fn (RouteInstance $route): bool => permissionFor($route) !== null)
            ->map(fn (RouteInstance $route): string => $route->methods()[0].' '.$route->uri())
            ->values()
            ->all();

        // An admin route without a permission is reachable by any signed-in
        // member. This fails the moment one is added.
        expect($unprotected)->toBe([]);
    });

    it('requires authentication', function (): void {
        $unauthenticated = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RouteInstance $route): bool => str_starts_with($route->uri(), 'admin'))
            ->reject(fn (RouteInstance $route): bool => in_array('auth', $route->gatherMiddleware(), true))
            ->map(fn (RouteInstance $route): string => $route->uri())
            ->values()
            ->all();

        expect($unauthenticated)->toBe([]);
    });
});

/*
 * These sweep the live route list inside the test body rather than through a
 * Pest dataset: datasets resolve before the application boots, so the router
 * is not available there.
 */

describe('guests', function (): void {
    it('are redirected to login from every admin route', function (): void {
        $reachable = [];

        foreach (adminGetRoutes() as [$uri]) {
            $status = $this->get('/'.$uri)->getStatusCode();

            if ($status !== 302) {
                $reachable[] = "{$uri} returned {$status}";
            }
        }

        expect($reachable)->toBe([]);
    });
});

describe('an ordinary member', function (): void {
    it('is forbidden from every admin route', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);

        $reachable = [];

        foreach (adminGetRoutes() as [$uri]) {
            $status = $this->actingAs($user)->get('/'.$uri)->getStatusCode();

            if ($status !== 403) {
                $reachable[] = "{$uri} returned {$status}";
            }
        }

        // Any entry here is a route a signed-in member can reach.
        expect($reachable)->toBe([]);
    });
});

describe('a Super Admin', function (): void {
    it('reaches every admin route', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Super Admin']);

        $blocked = [];

        foreach (adminGetRoutes() as [$uri]) {
            $status = $this->actingAs($user)->get('/'.$uri)->getStatusCode();

            if ($status < 200 || $status >= 300) {
                $blocked[] = "{$uri} returned {$status}";
            }
        }

        expect($blocked)->toBe([]);
    });
});

describe('role scoping', function (): void {
    it('forbids a role from a route it lacks the permission for', function (
        string $role,
        string $uri,
    ): void {
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        $this->actingAs($user)->get('/'.$uri)->assertForbidden();
    })->with([
        // A Content Manager has no business editing roles.
        ['Content Manager', 'admin/roles'],
        ['Moderator', 'admin/roles'],
        ['Event Manager', 'admin/roles'],
        ['Finance Manager', 'admin/roles'],
        ['Volunteer Coordinator', 'admin/roles'],
        ['Batch Coordinator', 'admin/roles'],
    ]);

    it('lets a role reach what it does hold', function (string $role, string $uri): void {
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        $this->actingAs($user)->get('/'.$uri)->assertSuccessful();
    })->with([
        ['Admin', 'admin'],
        ['Content Manager', 'admin'],
        ['Moderator', 'admin'],
        ['Finance Manager', 'admin'],
    ]);
});

describe('role management', function (): void {
    it('refuses to edit a protected role', function (string $roleName): void {
        $user = User::factory()->create();
        $user->syncRoles(['Super Admin']);

        $role = Role::findByName($roleName);
        $before = $role->permissions->pluck('name')->sort()->values()->all();

        $this->actingAs($user)
            ->put("/admin/roles/{$role->id}", ['permissions' => []])
            ->assertRedirect();

        $after = $role->fresh()->permissions->pluck('name')->sort()->values()->all();

        // Stripping Super Admin could lock the committee out of their own
        // platform permanently.
        expect($after)->toBe($before);
    })->with(['Super Admin', 'Member']);

    it('updates an editable role', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Super Admin']);

        $role = Role::findByName('Moderator');

        $this->actingAs($user)
            ->put("/admin/roles/{$role->id}", [
                'permissions' => ['admin.access', 'members.view'],
            ])
            ->assertRedirect();

        expect($role->fresh()->permissions->pluck('name')->sort()->values()->all())
            ->toBe(['admin.access', 'members.view']);
    });

    it('rejects a permission name that does not exist', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Super Admin']);

        $role = Role::findByName('Moderator');

        $this->actingAs($user)
            ->put("/admin/roles/{$role->id}", [
                'permissions' => ['members.view', 'everything.forever'],
            ])
            // 'everything.forever' is at index 1.
            ->assertSessionHasErrors('permissions.1');
    });

    it('forbids a user without roles.manage from updating any role', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Content Manager']);

        $role = Role::findByName('Moderator');

        $this->actingAs($user)
            ->put("/admin/roles/{$role->id}", ['permissions' => []])
            ->assertForbidden();
    });
});
