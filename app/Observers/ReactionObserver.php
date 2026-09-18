<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Reaction;
use App\Services\Community\PostCounters;

/**
 * Keeps `posts.reactions_count` true.
 *
 * Deliberately does NOT touch `last_activity_at`: a post should not climb back
 * to the top of the feed because one person tapped a heart on it.
 */
class ReactionObserver
{
    public function __construct(
        private readonly PostCounters $counters,
    ) {}

    public function created(Reaction $reaction): void
    {
        $this->counters->syncFor($reaction);
    }

    public function deleted(Reaction $reaction): void
    {
        $this->counters->syncFor($reaction);
    }
}
