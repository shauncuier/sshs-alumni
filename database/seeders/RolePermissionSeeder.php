<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles and permissions.
 *
 * Idempotent: re-running after adding a permission is safe, and roles keep any
 * permissions an administrator granted them through the admin UI that are not
 * listed here — the matrix below is a starting point, not a cage.
 *
 * Permission names are `{module}.{action}`. Nothing in the application checks
 * a ROLE name except the Super Admin bypass and the three scoped policies;
 * everything else checks permissions, so the committee can re-cut roles from
 * the admin panel without a deployment.
 *
 * @see docs/04-roles-permissions.md
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<string, array<int, string>>
     */
    private const PERMISSIONS = [
        'admin' => ['access'],
        'members' => ['view', 'create', 'edit', 'verify', 'delete', 'export'],
        'crm' => ['view', 'manage', 'assign', 'delete'],
        'batches' => ['view', 'create', 'edit', 'delete'],
        'events' => ['view', 'create', 'edit', 'publish', 'checkin', 'delete'],
        'payments' => ['view', 'create', 'edit', 'refund'],
        'donations' => ['view', 'manage'],
        'sponsors' => ['view', 'manage'],
        'volunteers' => ['view', 'manage'],
        'committees' => ['view', 'manage'],
        'community' => ['view', 'moderate', 'delete'],
        'content' => ['view', 'manage', 'publish'],
        'campaigns' => ['manage', 'send'],
        'reports' => ['view', 'export'],
        'users' => ['manage'],
        'roles' => ['manage'],
        'audit' => ['view'],
        'settings' => ['manage'],
    ];

    /**
     * Role definitions. A `*` grants every permission in that module.
     *
     * Scoping is NOT expressed here: a Batch Coordinator holds members.view but
     * a policy narrows their reach to their own batch. A permission grants a
     * capability; a policy grants reach.
     *
     * @var array<string, array<int, string>>
     */
    private const ROLES = [
        'Super Admin' => ['*'],

        'Admin' => ['*'],

        'CRM Manager' => [
            'admin.access',
            'members.view', 'members.export',
            'crm.view', 'crm.manage', 'crm.assign',
            'batches.view', 'events.view', 'committees.view',
            'payments.view', 'donations.view', 'donations.manage',
            'sponsors.view', 'sponsors.manage', 'volunteers.view',
            'campaigns.manage', 'reports.view', 'reports.export',
        ],

        'Membership Manager' => [
            'admin.access',
            'members.view', 'members.create', 'members.edit', 'members.verify', 'members.export',
            'crm.view',
            'batches.view', 'batches.create', 'batches.edit',
            'events.view', 'committees.view',
            'campaigns.manage', 'reports.view', 'reports.export',
        ],

        'Event Manager' => [
            'admin.access',
            'members.view',
            'batches.view',
            'events.view', 'events.create', 'events.edit', 'events.publish', 'events.checkin',
            'payments.view', 'sponsors.view', 'volunteers.view',
            'committees.view', 'content.view',
            'reports.view', 'reports.export',
        ],

        'Finance Manager' => [
            'admin.access',
            'members.view', 'crm.view',
            'events.view', 'committees.view',
            'payments.view', 'payments.create', 'payments.edit', 'payments.refund',
            'donations.view', 'donations.manage',
            'sponsors.view', 'sponsors.manage',
            'reports.view', 'reports.export',
        ],

        'Content Manager' => [
            'admin.access',
            'batches.view', 'events.view', 'sponsors.view',
            'committees.view', 'committees.manage',
            'community.view',
            'content.view', 'content.manage', 'content.publish',
            'campaigns.manage',
        ],

        'Moderator' => [
            'admin.access',
            'members.view',
            'batches.view', 'committees.view',
            'community.view', 'community.moderate', 'community.delete',
            'content.view',
        ],

        // Scoped to their own batch by policy, not by permission name.
        'Batch Coordinator' => [
            'admin.access',
            'members.view',
            'batches.view', 'batches.edit',
            'events.view', 'committees.view',
            'community.view', 'community.moderate',
            'content.view', 'content.manage',
        ],

        'Volunteer Coordinator' => [
            'admin.access',
            'members.view',
            'batches.view', 'events.view', 'events.checkin',
            'committees.view',
            'volunteers.view', 'volunteers.manage',
        ],

        // The default role for every approved alumnus. Access to their OWN
        // records is a policy, not a permission.
        'Member' => [
            'batches.view',
            'events.view',
            'committees.view',
            'community.view',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $created = $this->createPermissions();

        foreach (self::ROLES as $roleName => $granted) {
            $role = Role::findOrCreate($roleName, 'web');

            $permissions = in_array('*', $granted, true)
                ? $created
                : array_values(array_intersect($created, $granted));

            $this->assertPermissionsExist($roleName, $granted, $created);

            // syncPermissions would strip anything an administrator granted
            // through the admin UI, so only missing permissions are added.
            $role->givePermissionTo(array_diff($permissions, $role->permissions->pluck('name')->all()));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array<int, string>
     */
    private function createPermissions(): array
    {
        $names = [];

        foreach (self::PERMISSIONS as $module => $actions) {
            foreach ($actions as $action) {
                $name = "{$module}.{$action}";
                Permission::findOrCreate($name, 'web');
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * A typo in the matrix above would silently grant a role nothing, so a
     * name that does not exist fails loudly at seed time.
     *
     * @param  array<int, string>  $granted
     * @param  array<int, string>  $existing
     */
    private function assertPermissionsExist(string $role, array $granted, array $existing): void
    {
        if (in_array('*', $granted, true)) {
            return;
        }

        $unknown = array_diff($granted, $existing);

        if ($unknown !== []) {
            throw new \RuntimeException(
                "Role [{$role}] references unknown permissions: ".implode(', ', $unknown)
            );
        }
    }
}
