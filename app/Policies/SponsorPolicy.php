<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sponsor;
use App\Models\User;

/**
 * Who may read and act on a sponsor.
 *
 * `is_public` decides the wall; this decides the record. A sponsor row carries
 * a contact name, a negotiated amount and a private agreement path — none of
 * which belongs to anyone without `sponsors.view`.
 *
 * Super Admin bypasses all of this via Gate::before.
 */
class SponsorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sponsors.view');
    }

    public function view(User $user, Sponsor $sponsor): bool
    {
        return $user->can('sponsors.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sponsors.manage');
    }

    public function update(User $user, Sponsor $sponsor): bool
    {
        return $user->can('sponsors.manage');
    }

    public function delete(User $user, Sponsor $sponsor): bool
    {
        return $user->can('sponsors.manage');
    }
}
