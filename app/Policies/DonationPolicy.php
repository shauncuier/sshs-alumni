<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Donation;
use App\Models\User;

/**
 * Who may read and act on a donation.
 *
 * Donations carry a donor's name, amount and message. Anyone able to read this
 * list can see who gave what — which is exactly why it sits behind
 * `donations.view` rather than the broader `payments.view`.
 *
 * Super Admin bypasses all of this via Gate::before.
 */
class DonationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('donations.view');
    }

    public function view(User $user, Donation $donation): bool
    {
        if ($this->isOwn($user, $donation)) {
            return true;
        }

        return $user->can('donations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('donations.manage');
    }

    public function update(User $user, Donation $donation): bool
    {
        return $user->can('donations.manage');
    }

    /**
     * Deleting a received donation would take money out of the ledger through
     * a side door. It is refunded through the payment instead.
     */
    public function delete(User $user, Donation $donation): bool
    {
        if ($donation->payments()->exists()) {
            return false;
        }

        return $user->can('donations.manage');
    }

    private function isOwn(User $user, Donation $donation): bool
    {
        $member = $user->member;

        return $member !== null && $donation->donor_member_id === $member->id;
    }
}
