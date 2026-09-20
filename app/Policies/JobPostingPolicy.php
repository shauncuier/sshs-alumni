<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\JobPosting;
use App\Models\User;

class JobPostingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->member !== null && $user->member->isApproved();
    }

    public function view(User $user, JobPosting $job): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->member !== null && $user->member->isApproved();
    }

    public function update(User $user, JobPosting $job): bool
    {
        return $this->isPoster($user, $job);
    }

    public function delete(User $user, JobPosting $job): bool
    {
        return $this->isPoster($user, $job);
    }

    private function isPoster(User $user, JobPosting $job): bool
    {
        return $user->member !== null && $user->member->id === $job->member_id;
    }
}
