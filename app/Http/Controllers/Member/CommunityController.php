<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Enums\MediaCollection;
use App\Enums\PostCategory;
use App\Enums\ReportReason;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Models\Batch;
use App\Models\Comment;
use App\Models\Member;
use App\Models\Post;
use App\Services\Community\MentionParser;
use App\Services\Media\MediaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The members-only community feed.
 *
 * VISIBILITY LIVES ON THE MODEL, not here — `Post::scopeVisibleTo()` and
 * `Post::isVisibleTo()` are the same two rules expressed for a list and for a
 * single row, so the feed and the post page cannot disagree about who may read
 * what. A controller that reimplemented the batch rule would be the place the
 * two drifted apart.
 *
 * @see docs/05-modules.md section 11
 */
class CommunityController extends Controller
{
    /**
     * How many photos may ride along with one post.
     *
     * Four, because the grid renders them two-up on a phone and a post with
     * eleven photos is an album, which is a different feature.
     */
    private const MAX_PHOTOS = 4;

    public function __construct(
        private readonly MentionParser $mentions,
        private readonly MediaService $media,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Post::class);

        $viewer = $request->user()?->member;

        $filters = [
            'category' => (string) $request->query('category', ''),
            'q' => trim((string) $request->query('q', '')),
            'mine' => $request->boolean('mine'),
        ];

        $posts = Post::query()
            ->visibleTo($viewer)
            ->with(['author.privacy', 'author.batch', 'batch', 'media', 'reactions'])
            ->when($filters['category'] !== '', fn (Builder $query) => $query->where('category', $filters['category']))
            ->when($filters['mine'] && $viewer !== null, fn (Builder $query) => $query->where('author_member_id', $viewer?->id))
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $needle = '%'.$filters['q'].'%';
                $query->where(fn (Builder $inner) => $inner
                    ->where('title', 'like', $needle)
                    ->orWhere('body', 'like', $needle));
            })
            // Pinned first, then whatever was last talked on. A reaction does
            // not count as being talked on — see PostCounters::touchActivity().
            ->orderByDesc('is_pinned')
            ->orderByDesc(DB::raw('COALESCE(last_activity_at, created_at)'))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('member/community', [
            'posts' => PostResource::collection($posts),
            'mentions' => $this->mentions->resolveMany($posts->getCollection()->pluck('body')),
            'filters' => [
                'category' => $filters['category'] === '' ? null : $filters['category'],
                'q' => $filters['q'] === '' ? null : $filters['q'],
                'mine' => $filters['mine'],
            ],
            'options' => [
                'categories' => PostCategory::options(),
                'reasons' => ReportReason::options(),
                'max_photos' => self::MAX_PHOTOS,
            ],
            'viewer' => [
                'batch' => $viewer?->batch_id === null
                    ? null
                    : Batch::query()->whereKey($viewer->batch_id)->value('name'),
            ],
        ]);
    }

    /**
     * One post and its thread.
     *
     * A post the reader may not see is a 404, not a 403. Telling somebody that
     * a batch post they cannot read exists is itself a small disclosure.
     */
    public function show(Request $request, Post $post): Response
    {
        $this->authorize('viewAny', Post::class);

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

        $bodies = $comments
            ->flatMap(fn (Comment $comment): array => [
                $comment->body,
                ...$comment->replies->pluck('body')->all(),
            ])
            ->push($post->body);

        return Inertia::render('member/community-post', [
            'post' => PostResource::make($post),
            'comments' => CommentResource::collection($comments),
            'mentions' => $this->mentions->resolveMany($bodies),
            'options' => [
                'reasons' => ReportReason::options(),
            ],
            'can' => [
                'comment' => $request->user()->can('create', [Comment::class, $post]),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Post::class);

        $validated = $request->validate([
            'category' => ['required', 'string', 'in:'.implode(',', PostCategory::values())],
            'title' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'photos' => ['nullable', 'array', 'max:'.self::MAX_PHOTOS],
            'photos.*' => $this->media->validationRules(MediaCollection::Gallery),
        ]);

        /** @var Member $member */
        $member = $request->user()?->member;

        $post = Post::query()->create([
            'author_member_id' => $member->id,
            'category' => $validated['category'],
            // A batch post goes to the author's OWN batch. There is no field
            // for choosing one: posting into a cohort you did not attend is
            // not a thing this application does.
            'batch_id' => $validated['category'] === PostCategory::Batch->value
                ? $member->batch_id
                : null,
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'comments_enabled' => true,
        ]);

        $this->attachPhotos($request, $post);

        return redirect()
            ->route('community.show', $post->ulid)
            ->with('success', __('member.community.posted'));
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        // Category and batch are NOT editable. Moving a post between rooms
        // after people have replied to it changes who can read the replies.
        $post->update($validated);

        return back()->with('success', __('common.states.saved'));
    }

    /**
     * The author takes their own post down, or a moderator does.
     *
     * Soft-deleted, so a post removed in anger at 2am is recoverable, and so a
     * report already filed against it still points at something.
     */
    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()
            ->route('community.index')
            ->with('success', __('member.community.deleted'));
    }

    private function attachPhotos(Request $request, Post $post): void
    {
        /** @var array<int, UploadedFile> $photos */
        $photos = $request->file('photos') ?? [];

        foreach (array_slice($photos, 0, self::MAX_PHOTOS) as $photo) {
            $this->media->store($photo, MediaCollection::Gallery, $post);
        }
    }
}
