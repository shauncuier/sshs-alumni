<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;
use Illuminate\Support\Facades\DB;

/**
 * Keeps `posts.comments_count` and `posts.reactions_count` true.
 *
 * WHY RECOUNT RATHER THAN INCREMENT. A counter that is incremented on create
 * and decremented on delete is correct only while those are the only two
 * things that happen. They are not: a moderator hides a comment, a soft-
 * deleted comment is restored, a post is merged into a report queue and its
 * comments are removed one by one. Every one of those paths would have to
 * remember to adjust the number, and the first one that forgot would leave a
 * post permanently claiming four comments while showing three.
 *
 * A recount is one indexed aggregate over a handful of rows. It cannot drift.
 *
 * @see docs/02-database-schema.md section 11
 */
class PostCounters
{
    /**
     * Recount one post, without touching `updated_at`.
     *
     * The timestamp matters: bumping it on every reaction would reorder a feed
     * sorted by activity every time somebody pressed Like, which is not what
     * "recent activity" means to a reader.
     */
    public function sync(Post $post): void
    {
        $comments = Comment::query()
            ->where('commentable_type', $post->getMorphClass())
            ->where('commentable_id', $post->getKey())
            ->where('status', CommentStatus::Published)
            ->count();

        $reactions = Reaction::query()
            ->where('reactable_type', $post->getMorphClass())
            ->where('reactable_id', $post->getKey())
            ->count();

        DB::table('posts')
            ->where('id', $post->getKey())
            ->update([
                'comments_count' => $comments,
                'reactions_count' => $reactions,
            ]);

        $post->forceFill([
            'comments_count' => $comments,
            'reactions_count' => $reactions,
        ])->syncOriginalAttributes(['comments_count', 'reactions_count']);
    }

    /**
     * Mark the post as having just been talked on.
     *
     * Separate from sync() because a reaction is not a conversation: the feed
     * orders by `last_activity_at`, and a post should not jump to the top
     * because one person tapped a heart on it.
     */
    public function touchActivity(Post $post): void
    {
        $now = now();

        DB::table('posts')
            ->where('id', $post->getKey())
            ->update(['last_activity_at' => $now]);

        $post->forceFill(['last_activity_at' => $now])
            ->syncOriginalAttributes(['last_activity_at']);
    }

    /**
     * Recount whatever a comment or reaction was attached to, when that thing
     * is a post. Reactions on comments carry no cached count.
     */
    public function syncFor(Comment|Reaction $item): void
    {
        $type = $item instanceof Comment
            ? $item->commentable_type
            : $item->reactable_type;

        if ($type !== (new Post)->getMorphClass()) {
            return;
        }

        $id = $item instanceof Comment
            ? $item->commentable_id
            : $item->reactable_id;

        $post = Post::query()->withTrashed()->whereKey($id)->first();

        if ($post !== null) {
            $this->sync($post);
        }
    }
}
