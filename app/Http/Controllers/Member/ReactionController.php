<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Enums\ReactionType;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Member;
use App\Models\Post;
use App\Services\Community\ReactionToggler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Reactions on posts and comments.
 *
 * Both endpoints are the same three lines around the same service, because the
 * reaction table is polymorphic and the rule ("one per member per item") is
 * enforced by a UNIQUE index rather than by either of them.
 *
 * @see App\Services\Community\ReactionToggler
 */
class ReactionController extends Controller
{
    public function __construct(
        private readonly ReactionToggler $toggler,
    ) {}

    public function post(Request $request, Post $post): RedirectResponse
    {
        $this->authorize('view', $post);

        /** @var Member $member */
        $member = $request->user()?->member;

        $this->toggler->toggle($post, $member, $this->type($request));

        return back();
    }

    public function comment(Request $request, Comment $comment): RedirectResponse
    {
        $this->authorize('view', $comment);

        /** @var Member $member */
        $member = $request->user()?->member;

        $this->toggler->toggle($comment, $member, $this->type($request));

        return back();
    }

    private function type(Request $request): ReactionType
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', ReactionType::values())],
        ]);

        return ReactionType::from($validated['type']);
    }
}
