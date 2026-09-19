<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

describe('staff user management', function (): void {
    it('lists users for managers with users.manage permission', function (): void {
        $admin = User::factory()->create();
        $admin->syncRoles(['Super Admin']);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/users/index')
                ->has('users.data')
                ->has('roles')
            );
    });

    it('creates a new staff user with assigned role', function (): void {
        $admin = User::factory()->create();
        $admin->syncRoles(['Super Admin']);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Finance Executive',
            'email' => 'finance@sshs-alumni.org',
            'phone' => '01711223344',
            'password' => 'SecurePass123!@#',
            'status' => UserStatus::Active->value,
            'roles' => ['Finance Manager'],
        ]);

        $response->assertRedirect('/admin/users');

        $user = User::where('email', 'finance@sshs-alumni.org')->first();
        expect($user)->not->toBeNull()
            ->and($user->hasRole('Finance Manager'))->toBeTrue()
            ->and($user->status)->toBe(UserStatus::Active);
    });

    it('prevents self-deletion of an administrative account', function (): void {
        $admin = User::factory()->create();
        $admin->syncRoles(['Super Admin']);

        $this->actingAs($admin)
            ->delete("/admin/users/{$admin->id}")
            ->assertSessionHas('error');

        expect(User::find($admin->id))->not->toBeNull();
    });

    it('prevents deleting the only remaining Super Admin', function (): void {
        // Ensure only one super admin exists
        User::role('Super Admin')->delete();
        $soleAdmin = User::factory()->create();
        $soleAdmin->syncRoles(['Super Admin']);

        // Another admin with users.manage trying to delete the sole super admin
        $manager = User::factory()->create();
        $manager->syncRoles(['Admin']); // Has users.manage

        $this->actingAs($manager)
            ->delete("/admin/users/{$soleAdmin->id}")
            ->assertSessionHas('error');

        expect(User::find($soleAdmin->id))->not->toBeNull();
    });
});
