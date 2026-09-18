<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Volunteer;

/**
 * Who may read and act on a volunteer.
 *
 * A volunteer record carries a phone number somebody gave so they could be
 * called about a shift. It is not directory data and does not follow the
 * member privacy flags, so it sits behind `volunteers.view`.
 *
 * Super Admin bypasses all of this via Gate::before.
 */
class VolunteerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('volunteers.view');
    }

    public function view(User $user, Volunteer $volunteer): bool
    {
        return $user->can('volunteers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('volunteers.manage');
    }

    public function update(User $user, Volunteer $volunteer): bool
    {
        return $user->can('volunteers.manage');
    }

    public function delete(User $user, Volunteer $volunteer): bool
    {
        return $user->can('volunteers.manage');
    }
}
