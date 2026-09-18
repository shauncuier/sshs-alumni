<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;

/**
 * Who may read, write and moderate a community post.
 *
 * THE ONE RULE WORTH STATING OUTRIGHT: a moderator cannot edit a member's
 * words. `update` is the author and nobody else. A moderator can hide a post,
 * remove it, close its comments or pin it — all of which are visible, all of
 * which are audited — but nothing in this application lets one person publish
 * different words under another person's name.
 *
 * Community access requires an APPROVED membership, checked here as well as in
 * the middleware. The middleware decides whether the door opens; this decides
 * what may be done once inside, and the two are not the same question.
 *
 * Super Admin bypasses all of this via Gate::before.
 */
class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('community.view') && $this->isApprovedMember($user);
    }

    public function view(User $user, Post $post): bool
    {
        if ($this->canModerate($user)) {
            return true;
        }

        return $this->viewAny($user) && $post->isVisibleTo($user->member);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * The author, while the post is still published.
     *
     * A hidden post is not editable back into acceptability: whatever a
     * moderator acted on has to stay readable to them exactly as it was.
     */
    public function update(User $user, Post $post): bool
    {
        return $this->isAuthor($user, $post)
            && $post->status === PostStatus::Published;
    }

    /**
     * The author may take their own post down. A moderator with
     * `community.delete` may too — but that is a different act, and it is
     * recorded against their name.
     */
    public function delete(User $user, Post $post): bool
    {
        return $this->isAuthor($user, $post) || $user->can('community.delete');
    }

    public function moderate(User $user): bool
    {
        return $this->canModerate($user);
    }

    /**
     * Reporting is for OTHER people's posts. Reporting your own is either a
     * mistake or an attempt to get a moderator's attention through the wrong
     * door — the author can simply delete it.
     */
    public function report(User $user, Post $post): bool
    {
        return $this->viewAny($user) && ! $this->isAuthor($user, $post);
    }

    private function isAuthor(User $user, Post $post): bool
    {
        $member = $user->member;

        return $member !== null && $member->id === $post->author_member_id;
    }

    private function canModerate(User $user): bool
    {
        return $user->can('community.moderate');
    }

    /**
     * A pending application has not been checked by anyone. The community is
     * not a public square: everything in it is addressed to verified alumni.
     */
    private function isApprovedMember(User $user): bool
    {
        return $user->member?->isApproved() ?? false;
    }
}
