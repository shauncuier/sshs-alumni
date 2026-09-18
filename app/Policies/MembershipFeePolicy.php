<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MembershipFee;
use App\Models\User;

/**
 * Who may read and act on a membership fee.
 *
 * Fees are money, so they follow the `payments.*` permissions rather than
 * `members.*` — the treasurer raises and settles them, not whoever can edit a
 * profile.
 *
 * A member may always read their own, which is what lets them see what they
 * owe without holding any admin permission.
 *
 * Super Admin bypasses all of this via Gate::before.
 */
class MembershipFeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payments.view');
    }

    public function view(User $user, MembershipFee $membershipFee): bool
    {
        $member = $user->member;

        if ($member !== null && $membershipFee->member_id === $member->id) {
            return true;
        }

        return $user->can('payments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payments.create');
    }

    public function update(User $user, MembershipFee $membershipFee): bool
    {
        return $user->can('payments.create');
    }

    public function delete(User $user, MembershipFee $membershipFee): bool
    {
        return $user->can('payments.create');
    }
}
