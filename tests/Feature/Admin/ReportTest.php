<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

describe('reporting and data extraction', function (): void {
    it('allows users with reports.view permission to see the reports catalog', function (): void {
        $admin = User::factory()->create();
        $admin->syncRoles(['Super Admin']);

        $response = $this->actingAs($admin)->get('/admin/reports');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/reports/index')
                ->has('reports.members')
                ->has('reports.payments')
                ->has('reports.batches')
            );
    });

    it('allows previewing a report with its data rows', function (): void {
        Member::factory()->count(3)->create(['mobile' => '01711223344']);

        $admin = User::factory()->create();
        $admin->syncRoles(['Super Admin']);

        $response = $this->actingAs($admin)->get('/admin/reports/members');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/reports/show')
                ->has('headers')
                ->has('rows')
                ->where('reportKey', 'members')
            );
    });

    it('denies export to users without reports.export permission', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']); // Lacks reports.export

        $this->actingAs($user)
            ->post('/admin/reports/members/export')
            ->assertForbidden();
    });

    it('streams CSV export and creates an audit log entry', function (): void {
        Member::factory()->count(2)->create(['mobile' => '01711223344']);

        $admin = User::factory()->create();
        $admin->syncRoles(['Super Admin']);

        $response = $this->actingAs($admin)->post('/admin/reports/members/export', [
            'status' => 'approved',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Verify that mandatory audit log was generated
        $audit = AuditLog::where('action', 'report.exported')->latest('id')->first();
        expect($audit)->not->toBeNull()
            ->and($audit->user_id)->toBe($admin->id)
            ->and($audit->after['report'])->toBe('members');
    });
});
