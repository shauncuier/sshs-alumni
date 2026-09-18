<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Comment;
use App\Models\Post;
use App\Services\Community\PostCounters;

/**
 * Keeps a post's comment count and activity time true.
 *
 * This is on the MODEL, not in the controller, because a comment is created
 * from three places already — the post page, the moderation queue's restore,
 * and the seeder — and will be created from more. A count maintained by
 * whoever remembers is a count that is wrong.
 *
 * `updated` matters as much as `created`: hiding a comment does not delete it,
 * but it does have to stop counting, or the number under the post says four
 * while the reader can see three.
 *
 * @see App\Services\Community\PostCounters
 */
class CommentObserver
{
    public function __construct(
        private readonly PostCounters $counters,
    ) {}

    public function created(Comment $comment): void
    {
        $this->counters->syncFor($comment);

        // A comment IS the activity the feed orders by. A reaction is not —
        // see PostCounters::touchActivity().
        $post = $comment->commentable;

        if ($post instanceof Post) {
            $this->counters->touchActivity($post);
        }
    }

    public function updated(Comment $comment): void
    {
        $this->counters->syncFor($comment);
    }

    public function deleted(Comment $comment): void
    {
        $this->counters->syncFor($comment);
    }

    public function restored(Comment $comment): void
    {
        $this->counters->syncFor($comment);
    }
}
