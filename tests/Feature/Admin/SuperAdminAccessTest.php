<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SuperAdminSeeder::class);
});

it('ensures super admin has universal access to all permissions and gates', function (): void {
    $admin = User::query()->where('email', config('auth.super_admin.email'))->firstOrFail();

    expect($admin->hasRole('Super Admin'))->toBeTrue();

    // Universal gate bypass
    expect($admin->can('admin.access'))->toBeTrue();
    expect($admin->can('members.view'))->toBeTrue();
    expect($admin->can('members.verify'))->toBeTrue();
    expect($admin->can('payments.refund'))->toBeTrue();
    expect($admin->can('roles.manage'))->toBeTrue();
    expect($admin->can('any.nonexistent.permission'))->toBeTrue();
});

it('allows super admin to access administrative routes without restriction', function (): void {
    $admin = User::query()->where('email', config('auth.super_admin.email'))->firstOrFail();

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($admin)->get(route('admin.members.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.batches.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.events.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.crm.contacts.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk();
    $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.audit.index'))->assertOk();
});

it('allows super admin to access member areas without approval roadblock', function (): void {
    $admin = User::query()->where('email', config('auth.super_admin.email'))->firstOrFail();

    expect($admin->member)->not->toBeNull();
    expect($admin->member->isApproved())->toBeTrue();

    $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    $this->actingAs($admin)->get(route('directory.index'))->assertOk();
    $this->actingAs($admin)->get(route('community.index'))->assertOk();
    $this->actingAs($admin)->get(route('my.card'))->assertOk();
});

it('allows super admin to view batch members and hidden profiles', function (): void {
    $admin = User::query()->where('email', config('auth.super_admin.email'))->firstOrFail();

    $batch = Batch::factory()->create([
        'slug' => 'ssc-2021',
        'ssc_year' => 2021,
    ]);

    $member = Member::factory()->create([
        'batch_id' => $batch->id,
        'status' => MemberStatus::Approved,
    ]);
    $member->privacy()->update(['show_profile' => false]);

    // Guest visiting batch show does not receive member roster
    $this->get(route('batches.show', $batch))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/batch-show')
            ->where('can_view_members', false)
            ->where('members', null)
        );

    // Super Admin visiting batch show receives member roster
    $this->actingAs($admin)->get(route('batches.show', $batch))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/batch-show')
            ->where('can_view_members', true)
            ->where('is_super_admin', true)
            ->has('members.data', 1)
        );

    // Super Admin can view a profile even if show_profile is false
    $this->actingAs($admin)->get(route('directory.show', $member))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('member/directory-show')
            ->where('member.ulid', $member->ulid)
        );
});
