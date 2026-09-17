<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Batch;
use App\Models\User;

class BatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('batches.view');
    }

    public function view(User $user, Batch $batch): bool
    {
        return $user->can('batches.view');
    }

    public function create(User $user): bool
    {
        return $user->can('batches.create');
    }

    /**
     * A Batch Coordinator may edit their own batch's description and cover,
     * and nothing else.
     */
    public function update(User $user, Batch $batch): bool
    {
        if (! $user->can('batches.edit')) {
            return false;
        }

        if (! $user->hasRole('Batch Coordinator')) {
            return true;
        }

        $coordinated = $user->member?->coordinatedBatches()->pluck('batches.id') ?? collect();

        return $coordinated->contains($batch->id);
    }

    public function delete(User $user, Batch $batch): bool
    {
        return $user->can('batches.delete');
    }
}
