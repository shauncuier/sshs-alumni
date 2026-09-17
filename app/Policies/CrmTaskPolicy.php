<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CrmTask;
use App\Models\User;

/**
 * Who may read and act on a CRM task.
 *
 * The assignee can always work their own task without holding `crm.manage` —
 * a volunteer coordinator given a follow-up to make should not need the
 * permission that lets them rewrite the CRM to tick it off.
 *
 * Super Admin bypasses all of this via Gate::before.
 */
class CrmTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.view');
    }

    public function view(User $user, CrmTask $task): bool
    {
        return $this->isOwn($user, $task) || $user->can('crm.view');
    }

    public function create(User $user): bool
    {
        return $user->can('crm.manage');
    }

    /**
     * The assignee may complete, reschedule or annotate their own task.
     */
    public function update(User $user, CrmTask $task): bool
    {
        return $this->isOwn($user, $task) || $user->can('crm.manage');
    }

    public function delete(User $user, CrmTask $task): bool
    {
        return $user->can('crm.manage');
    }

    /**
     * Assigned to them, or raised by them.
     */
    private function isOwn(User $user, CrmTask $task): bool
    {
        return $task->assigned_to === $user->id || $task->created_by === $user->id;
    }
}
