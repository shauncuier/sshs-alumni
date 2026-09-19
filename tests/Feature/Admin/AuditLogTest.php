<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

describe('audit logs explorer', function (): void {
    it('allows users with audit.view permission to browse audit trail', function (): void {
        $admin = User::factory()->create();
        $admin->syncRoles(['Super Admin']);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'member.approved',
            'auditable_type' => 'App\\Models\\Member',
            'auditable_id' => 1,
            'before' => ['status' => 'pending'],
            'after' => ['status' => 'approved'],
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($admin)->get('/admin/audit-logs');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/audit/index')
                ->has('logs.data')
                ->has('availableActions')
                ->has('staffUsers')
            );
    });

    it('denies access to users without audit.view permission', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);

        $this->actingAs($user)
            ->get('/admin/audit-logs')
            ->assertForbidden();
    });

    it('filters audit logs by action and user', function (): void {
        $admin1 = User::factory()->create();
        $admin2 = User::factory()->create();
        $admin1->syncRoles(['Super Admin']);
        $admin2->syncRoles(['Super Admin']);

        AuditLog::create([
            'user_id' => $admin1->id,
            'action' => 'member.approved',
            'ip_address' => '127.0.0.1',
        ]);

        AuditLog::create([
            'user_id' => $admin2->id,
            'action' => 'payment.created',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($admin1)->get("/admin/audit-logs?action=member.approved&user_id={$admin1->id}");

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/audit/index')
                ->has('logs.data', 1)
                ->where('logs.data.0.action', 'member.approved')
            );
    });
});
