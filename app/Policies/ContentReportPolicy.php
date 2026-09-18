<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ContentReport;
use App\Models\User;

/**
 * The moderation queue.
 *
 * Reports are never deleted. A queue that can be emptied by deleting the
 * awkward entries is not a record of anything — `dismissed` is how a
 * moderator says nothing was wrong, and it stays on the file.
 *
 * Super Admin bypasses all of this via Gate::before.
 */
class ContentReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('community.moderate');
    }

    public function view(User $user, ContentReport $report): bool
    {
        return $user->can('community.moderate');
    }

    /**
     * Any approved member may report. Deliberately not permission-gated
     * beyond that: the report button is the community's own safety valve, and
     * putting a permission on it would mean somebody could be prevented from
     * saying they were being harassed.
     */
    public function create(User $user): bool
    {
        return $user->member?->isApproved() ?? false;
    }

    public function resolve(User $user, ContentReport $report): bool
    {
        return $user->can('community.moderate');
    }
}
