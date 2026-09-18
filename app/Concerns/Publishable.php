<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Content that is draft until somebody says otherwise.
 *
 * TWO CONDITIONS, NOT ONE. A row is public when its status is `published` AND
 * its `published_at` is not in the future. The date is what lets the committee
 * write a news item on Tuesday for a Friday morning, and a scope that checked
 * only the status would publish it the moment it was saved — which is exactly
 * the mistake that cannot be taken back, because the search engines will have
 * had it.
 *
 * `published_at` being null means "as soon as it is published", so a null is
 * visible rather than hidden. Publishing through `publish()` stamps it, so the
 * null case only ever comes from a direct write.
 *
 * @see docs/05-modules.md section 12
 */
trait Publishable
{
    /**
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query
            ->where('status', ContentStatus::Published)
            ->where(function (Builder $inner): void {
                $inner->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeDrafts(Builder $query): void
    {
        $query->where('status', ContentStatus::Draft);
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published
            && ($this->published_at === null || $this->published_at->isPast());
    }

    /**
     * Publish now, keeping an earlier date if one was set.
     *
     * Keeping it matters: re-publishing something after an edit must not move
     * it to the top of a list ordered by publication date, or every correction
     * to an old news item republishes it to the front page.
     */
    public function publish(): static
    {
        $this->forceFill([
            'status' => ContentStatus::Published,
            'published_at' => $this->published_at ?? now(),
        ])->save();

        return $this;
    }

    public function unpublish(): static
    {
        // The date stays. It records when this was first published, which is
        // still true after it is taken down.
        $this->forceFill(['status' => ContentStatus::Draft])->save();

        return $this;
    }
}
