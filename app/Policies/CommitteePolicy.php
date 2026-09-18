<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Committee;
use App\Models\User;

/**
 * Who may read and act on a committee.
 *
 * Reading is wide — every role holds `committees.view`, because knowing who
 * runs the association is the point. Changing who sits on one is narrow.
 *
 * Super Admin bypasses all of this via Gate::before.
 */
class CommitteePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('committees.view');
    }

    public function view(User $user, Committee $committee): bool
    {
        return $user->can('committees.view');
    }

    public function create(User $user): bool
    {
        return $user->can('committees.manage');
    }

    public function update(User $user, Committee $committee): bool
    {
        return $user->can('committees.manage');
    }

    public function delete(User $user, Committee $committee): bool
    {
        return $user->can('committees.manage');
    }
}
