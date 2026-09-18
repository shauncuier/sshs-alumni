<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;

/**
 * Who may read and act on the ledger.
 *
 * There is no `update` and no `delete`, deliberately. A payment is never
 * edited into a different amount and never removed — a mistake is corrected by
 * refunding and re-recording, and both are audited. A policy method for
 * editing one would invite a controller to exist for it.
 *
 * Super Admin bypasses all of this via Gate::before.
 *
 * @see docs/09-payments.md section 4
 */
class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        // A member may read their own receipt without holding payments.view.
        if ($this->isOwn($user, $payment)) {
            return true;
        }

        return $user->can('payments.view');
    }

    /**
     * Recording money received. The audit row naming this user is what makes
     * offline cash handling traceable at all.
     */
    public function create(User $user): bool
    {
        return $user->can('payments.create');
    }

    /**
     * Refunding is separate from recording, and deliberately narrower: giving
     * money back is the action a volunteer-run committee most needs to be able
     * to point at afterwards.
     */
    public function refund(User $user, Payment $payment): bool
    {
        if ($payment->status !== PaymentStatus::Paid) {
            return false;
        }

        return $user->can('payments.refund');
    }

    private function isOwn(User $user, Payment $payment): bool
    {
        $member = $user->member;

        return $member !== null && $payment->payer_member_id === $member->id;
    }
}
