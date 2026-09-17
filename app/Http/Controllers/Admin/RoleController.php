<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role and permission management.
 *
 * Lets the committee re-cut roles without a deployment, which is the whole
 * reason application code checks permissions rather than role names.
 *
 * @see docs/04-roles-permissions.md
 */
class RoleController extends Controller
{
    /**
     * Roles that cannot be edited or deleted.
     *
     * Super Admin bypasses every gate, so editing its permissions would be
     * meaningless; deleting it could lock the committee out of their own
     * platform permanently.
     *
     * @var array<int, string>
     */
    private const PROTECTED_ROLES = ['Super Admin', 'Member'];

    public function index(): Response
    {
        $roles = Role::query()
            ->with('permissions:id,name')
            ->withCount('users')
            ->orderBy('id')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users_count,
                'permissions' => $role->permissions->pluck('name')->all(),
                'is_protected' => in_array($role->name, self::PROTECTED_ROLES, true),
            ]);

        return Inertia::render('admin/roles/index', [
            'roles' => $roles,
            'permissionGroups' => $this->groupedPermissions(),
        ]);
    }

    public function update(RoleUpdateRequest $request, Role $role): RedirectResponse
    {
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return back()->with('error', __('admin.roles.protected', ['role' => $role->name]));
        }

        /** @var array<int, string> $permissions */
        $permissions = $request->validated('permissions', []);

        // Only permissions that actually exist — a forged name in the payload
        // must not create one.
        $valid = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('name')
            ->all();

        $role->syncPermissions($valid);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', __('admin.roles.updated', ['role' => $role->name]));
    }

    /**
     * Permissions grouped by module, so the matrix is navigable rather than a
     * flat list of 47 checkboxes.
     *
     * @return array<string, array<int, array{name: string, action: string}>>
     */
    private function groupedPermissions(): array
    {
        $grouped = [];

        foreach (Permission::query()->orderBy('name')->pluck('name') as $name) {
            $module = Str::before((string) $name, '.');

            $grouped[$module][] = [
                'name' => (string) $name,
                'action' => Str::after((string) $name, '.'),
            ];
        }

        return $grouped;
    }
}
