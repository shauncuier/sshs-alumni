<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BusinessListing;
use App\Models\User;

class BusinessListingPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, BusinessListing $business): bool
    {
        if ($business->isPublished()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $this->isOwner($user, $business);
    }

    public function create(User $user): bool
    {
        return $user->member !== null && $user->member->isApproved();
    }

    public function update(User $user, BusinessListing $business): bool
    {
        return $this->isOwner($user, $business);
    }

    public function delete(User $user, BusinessListing $business): bool
    {
        return $this->isOwner($user, $business);
    }

    private function isOwner(User $user, BusinessListing $business): bool
    {
        return $user->member !== null && $user->member->id === $business->member_id;
    }
}
