<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Comments on a community post.
 *
 * Threaded ONE level. A reply to a reply attaches to the same parent, because
 * a thread that nests forever is unreadable on a phone — which is where most
 * of this association reads it.
 *
 * @see docs/05-modules.md section 11
 */
class CommentController extends Controller
{
    public function store(Request $request, Post $post): RedirectResponse
    {
        $this->authorize('create', [Comment::class, $post]);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        /** @var Member $member */
        $member = $request->user()?->member;

        Comment::query()->create([
            'commentable_type' => $post->getMorphClass(),
            'commentable_id' => $post->id,
            'author_member_id' => $member->id,
            'parent_id' => $this->resolveParent($post, $validated['parent_id'] ?? null),
            'body' => $validated['body'],
        ]);

        // The count and `last_activity_at` are the observer's job, not this
        // controller's — see CommentObserver.
        return back()->with('success', __('member.community.commented'));
    }

    /**
     * Take a comment down.
     *
     * Soft delete: a report already filed against it has to keep pointing at
     * something a moderator can read.
     */
    public function destroy(Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return back()->with('success', __('member.community.comment_deleted'));
    }

    /**
     * The parent this reply really attaches to.
     *
     * A reply aimed at a reply is re-pointed at the top-level comment rather
     * than rejected: the member did nothing wrong, and their words belong in
     * the conversation they were answering. A parent from a DIFFERENT post is
     * dropped entirely — that is not a mistake a person makes by hand.
     */
    private function resolveParent(Post $post, ?int $parentId): ?int
    {
        if ($parentId === null) {
            return null;
        }

        $parent = Comment::query()->whereKey($parentId)->first();

        if ($parent === null) {
            return null;
        }

        if ($parent->commentable_type !== $post->getMorphClass() || $parent->commentable_id !== $post->id) {
            return null;
        }

        return $parent->parent_id ?? $parent->id;
    }
}
