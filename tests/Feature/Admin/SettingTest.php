<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\Settings\SettingsService;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

describe('system settings management', function (): void {
    it('allows an authorized administrator to view settings', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Super Admin']);

        $response = $this->actingAs($user)->get('/admin/settings?group=organization');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/settings/edit')
                ->has('groups')
                ->has('allSettings')
                ->where('currentGroup', 'organization')
            );
    });

    it('denies access to unauthorized users', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']); // Member lacks settings.manage

        $this->actingAs($user)
            ->get('/admin/settings')
            ->assertForbidden();
    });

    it('updates settings group and refreshes the settings cache', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Super Admin']);

        $service = app(SettingsService::class);

        $response = $this->actingAs($user)->put('/admin/settings/school', [
            'settings' => [
                'name' => 'Sabuj Shikshayatan High School',
                'name_bn' => 'সবুজ শিক্ষায়তন উচ্চ বিদ্যালয়',
                'established_year' => '1976',
                'eiin' => '105070',
                'board' => 'Chattogram',
            ],
        ]);

        $response->assertRedirect();

        expect($service->get('school.established_year'))->toBe('1976')
            ->and($service->get('school.eiin'))->toBe('105070');
    });

    it('maintains strict distinction between school (1976) and association (2015)', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Super Admin']);

        $this->actingAs($user)->put('/admin/settings/organization', [
            'settings' => [
                'established_year' => '2015',
                'name' => 'SSHS Alumni Association',
            ],
        ]);

        $this->actingAs($user)->put('/admin/settings/school', [
            'settings' => [
                'established_year' => '1976',
                'name' => 'Sabuj Shikshayatan High School',
            ],
        ]);

        $service = app(SettingsService::class);

        expect($service->get('organization.established_year'))->toBe('2015')
            ->and($service->get('school.established_year'))->toBe('1976');
    });
});
