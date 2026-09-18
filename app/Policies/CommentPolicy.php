<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

/**
 * Who may comment, and who may take a comment down.
 *
 * As with posts, a moderator cannot edit a comment — only hide or remove it.
 *
 * Super Admin bypasses all of this via Gate::before.
 */
class CommentPolicy
{
    public function view(User $user, Comment $comment): bool
    {
        if ($user->can('community.moderate')) {
            return true;
        }

        if ($comment->status !== CommentStatus::Published && ! $this->isAuthor($user, $comment)) {
            return false;
        }

        $post = $comment->commentable;

        return $post instanceof Post
            ? $post->isVisibleTo($user->member)
            : false;
    }

    /**
     * Commenting needs the post to be visible AND open.
     *
     * `comments_enabled` is the useful middle setting for a thread that has
     * run its course: the post stays readable while the argument stops.
     */
    public function create(User $user, Post $post): bool
    {
        if (! ($user->member?->isApproved() ?? false) || ! $user->can('community.view')) {
            return false;
        }

        return $post->comments_enabled && $post->isVisibleTo($user->member);
    }

    public function update(User $user, Comment $comment): bool
    {
        return $this->isAuthor($user, $comment)
            && $comment->status === CommentStatus::Published;
    }

    /**
     * The comment's author, the POST's author, or a moderator.
     *
     * The post author is in here on purpose: somebody who starts a thread is
     * responsible for it, and expecting them to wait for a moderator to remove
     * an insult under their own memorial post is not a reasonable ask.
     */
    public function delete(User $user, Comment $comment): bool
    {
        if ($this->isAuthor($user, $comment) || $user->can('community.delete')) {
            return true;
        }

        $post = $comment->commentable;
        $member = $user->member;

        return $post instanceof Post
            && $member !== null
            && $post->author_member_id === $member->id;
    }

    public function report(User $user, Comment $comment): bool
    {
        return ($user->member?->isApproved() ?? false)
            && $user->can('community.view')
            && ! $this->isAuthor($user, $comment);
    }

    private function isAuthor(User $user, Comment $comment): bool
    {
        $member = $user->member;

        return $member !== null && $member->id === $comment->author_member_id;
    }
}
