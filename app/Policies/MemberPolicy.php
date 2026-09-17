<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

/**
 * Who may read and act on a member record.
 *
 * A permission grants a CAPABILITY; this policy grants REACH. A Batch
 * Coordinator holds `members.view` but only over their own batch, and that
 * narrowing lives here rather than in the permission name — otherwise every
 * caller would have to remember to apply it.
 *
 * Super Admin bypasses all of this via Gate::before.
 *
 * @see docs/04-roles-permissions.md section 5
 */
class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('members.view');
    }

    public function view(User $user, Member $member): bool
    {
        if ($this->isOwn($user, $member)) {
            return true;
        }

        if (! $user->can('members.view')) {
            return false;
        }

        return $this->withinReach($user, $member);
    }

    public function create(User $user): bool
    {
        return $user->can('members.create');
    }

    public function update(User $user, Member $member): bool
    {
        // A member edits their own profile without holding members.edit.
        if ($this->isOwn($user, $member)) {
            return true;
        }

        return $user->can('members.edit') && $this->withinReach($user, $member);
    }

    /**
     * Approving, rejecting, suspending, requesting a correction, assigning a
     * membership number.
     *
     * Deliberately NOT available on your own record: nobody approves
     * themselves, whatever permissions they hold.
     */
    public function verify(User $user, Member $member): bool
    {
        if ($this->isOwn($user, $member)) {
            return false;
        }

        return $user->can('members.verify');
    }

    public function delete(User $user, Member $member): bool
    {
        if ($this->isOwn($user, $member)) {
            return false;
        }

        return $user->can('members.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('members.export');
    }

    private function isOwn(User $user, Member $member): bool
    {
        return $member->user_id !== null && $member->user_id === $user->id;
    }

    /**
     * A Batch Coordinator sees only their own batch. Everyone else with the
     * permission sees everyone.
     */
    private function withinReach(User $user, Member $member): bool
    {
        if (! $user->hasRole('Batch Coordinator')) {
            return true;
        }

        $coordinated = $user->member?->coordinatedBatches()->pluck('batches.id') ?? collect();

        return $member->batch_id !== null && $coordinated->contains($member->batch_id);
    }
}
