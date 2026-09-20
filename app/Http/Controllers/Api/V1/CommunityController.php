<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PostCategory;
use App\Enums\ReactionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Models\Comment;
use App\Models\Post;
use App\Services\Community\ReactionToggler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Community API for approved members.
 *
 * Visibility rules come from `Post::scopeVisibleTo()`, so the feed
 * never disagrees with the web about who may read what.
 *
 * @see docs/10-api.md section 3
 * @see docs/05-modules.md section 11
 */
class CommunityController extends Controller
{
    public function __construct(
        private readonly ReactionToggler $reactions,
    ) {}

    /**
     * The community feed — paginated, filterable.
     */
    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user()?->member;

        $category = (string) $request->query('category', '');

        $posts = Post::query()
            ->visibleTo($viewer)
            ->with(['author.privacy', 'author.batch', 'batch', 'media', 'reactions'])
            ->when($category !== '', fn (Builder $query) => $query->where('category', $category))
            ->orderByDesc('is_pinned')
            ->orderByDesc(DB::raw('COALESCE(last_activity_at, created_at)'))
            ->paginate(min(50, max(1, (int) $request->query('per_page', 15))));

        return response()->json([
            'posts' => PostResource::collection($posts)->response()->getData(true),
        ]);
    }

    /**
     * A single post with its comment thread.
     */
    public function show(Request $request, Post $post): JsonResponse
    {
        abort_unless($request->user()?->can('view', $post) ?? false, 404);

        $post->load(['author.privacy', 'author.batch', 'batch', 'media', 'reactions']);

        $comments = $post->comments()
            ->published()
            ->whereNull('parent_id')
            ->with([
                'author.privacy',
                'author.batch',
                'reactions',
                'replies' => fn ($query) => $query->published()
                    ->with(['author.privacy', 'author.batch', 'reactions'])
                    ->oldest(),
            ])
            ->oldest()
            ->get();

        return response()->json([
            'post' => PostResource::make($post)->resolve(),
            'comments' => CommentResource::collection($comments)->resolve(),
        ]);
    }

    /**
     * Create a new community post.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Post::class);

        $member = $request->user()?->member;
        abort_if($member === null, 403, 'A verified member profile is required.');

        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in(PostCategory::values())],
            'title' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $post = Post::query()->create([
            'author_member_id' => $member->id,
            'category' => $validated['category'],
            'batch_id' => $validated['category'] === PostCategory::Batch->value
                ? $member->batch_id
                : null,
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'comments_enabled' => true,
        ]);

        $post->refresh();
        $post->load(['author.privacy', 'author.batch', 'batch']);

        return response()->json([
            'message' => 'Post created successfully.',
            'post' => PostResource::make($post)->resolve(),
        ], 201);
    }

    /**
     * Add a comment to a post.
     */
    public function comment(Request $request, Post $post): JsonResponse
    {
        $this->authorize('create', [Comment::class, $post]);

        $member = $request->user()?->member;
        abort_if($member === null, 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        // If replying, ensure the parent belongs to the same post.
        if (! empty($validated['parent_id'])) {
            $parent = Comment::query()
                ->where('commentable_type', Post::class)
                ->where('commentable_id', $post->id)
                ->whereKey($validated['parent_id'])
                ->first();
            abort_if($parent === null, 422, 'The parent comment does not belong to this post.');
        }

        $comment = $post->comments()->create([
            'author_member_id' => $member->id,
            'body' => $validated['body'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $comment->refresh();
        $comment->load(['author.privacy', 'author.batch']);

        return response()->json([
            'message' => 'Comment added.',
            'comment' => CommentResource::make($comment)->resolve(),
        ], 201);
    }

    /**
     * Toggle a reaction on a post.
     */
    public function react(Request $request, Post $post): JsonResponse
    {
        abort_unless($request->user()?->can('view', $post) ?? false, 404);

        $member = $request->user()?->member;
        abort_if($member === null, 403);

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(ReactionType::values())],
        ]);

        $standing = $this->reactions->toggle($post, $member, ReactionType::from($validated['type']));

        return response()->json([
            'toggled' => $standing !== null,
            'reaction' => $standing?->value,
            'reactions_count' => $post->fresh()->reactions_count,
        ]);
    }
}
