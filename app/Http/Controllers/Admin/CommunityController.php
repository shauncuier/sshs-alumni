<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CommentStatus;
use App\Enums\PostCategory;
use App\Enums\PostStatus;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\Post;
use App\Models\User;
use App\Services\Community\ModerationService;
use App\Support\Paginated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The moderation queue.
 *
 * Two screens, deliberately: the POSTS list is everything, for the moderator
 * who has been told about something; the REPORTS queue is what members have
 * flagged, which is the work. They are not the same job and merging them would
 * bury the second in the first.
 *
 * Nothing here edits a member's words. Every action is hide, remove, restore,
 * pin or close-comments, and every one of them is audited with the reason.
 *
 * @see App\Services\Community\ModerationService
 */
class CommunityController extends Controller
{
    public function __construct(
        private readonly ModerationService $moderation,
    ) {}

    public function posts(Request $request): Response
    {
        $this->authorize('moderate', Post::class);

        $filters = [
            'status' => (string) $request->query('status', ''),
            'category' => (string) $request->query('category', ''),
            'q' => trim((string) $request->query('q', '')),
        ];

        $posts = Post::query()
            ->withTrashed()
            // The author is loaded WITH TRASHED. A member can be soft-deleted
            // while their posts stand, and a moderation queue that fataled on
            // the first such row would be unusable exactly when somebody was
            // cleaning up after a departure.
            ->with(['author' => fn ($author) => $author->withTrashed(), 'batch'])
            ->withCount([
                'reports as open_reports_count' => fn (Builder $query) => $query
                    ->whereIn('status', [ReportStatus::Open, ReportStatus::Reviewing]),
            ])
            ->when($filters['status'] !== '', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['category'] !== '', fn (Builder $query) => $query->where('category', $filters['category']))
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $needle = '%'.$filters['q'].'%';
                $query->where(fn (Builder $inner) => $inner
                    ->where('title', 'like', $needle)
                    ->orWhere('body', 'like', $needle));
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/community/posts', [
            'posts' => Paginated::from($posts, fn (Post $post): array => $this->postRow($post)),
            'filters' => array_map(
                fn (string $value): ?string => $value === '' ? null : $value,
                $filters,
            ),
            'options' => [
                'statuses' => PostStatus::options(),
                'categories' => PostCategory::options(),
            ],
            'open_reports' => ContentReport::query()
                ->whereIn('status', [ReportStatus::Open, ReportStatus::Reviewing])
                ->count(),
        ]);
    }

    public function reports(Request $request): Response
    {
        $this->authorize('viewAny', ContentReport::class);

        $status = (string) $request->query('status', ReportStatus::Open->value);

        $reports = ContentReport::query()
            ->with(['reporter', 'resolver', 'reportable' => function ($reportable): void {
                // Same reason as the posts list: the author of a reported
                // post may since have been removed, and the report still has
                // to be readable.
                $reportable->morphWith([
                    Post::class => ['author' => fn ($author) => $author->withTrashed()],
                    Comment::class => [
                        'author' => fn ($author) => $author->withTrashed(),
                        'commentable',
                    ],
                ]);
            }])
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            // Oldest first. A queue worked newest-first leaves the report
            // nobody got to at the bottom forever.
            ->orderBy('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/community/reports', [
            'reports' => Paginated::from($reports, fn (ContentReport $report): array => $this->reportRow($report)),
            'filters' => ['status' => $status],
            'options' => [
                'statuses' => ReportStatus::options(),
                'reasons' => ReportReason::options(),
            ],
        ]);
    }

    /**
     * Hide, remove or restore a post; pin it; open or close its comments.
     *
     * One endpoint rather than five, because a moderator working a report does
     * these in combination and each one is the same shape: an intent and a
     * reason.
     */
    public function updatePost(Request $request, Post $post): RedirectResponse
    {
        $this->authorize('moderate', Post::class);

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', PostStatus::values())],
            'is_pinned' => ['nullable', 'boolean'],
            'comments_enabled' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $actor */
        $actor = $request->user();

        if (isset($validated['status'])) {
            $this->moderation->setPostStatus(
                $post,
                PostStatus::from($validated['status']),
                $actor,
                $validated['reason'] ?? null,
            );
        }

        if (array_key_exists('is_pinned', $validated) && $validated['is_pinned'] !== null) {
            $this->moderation->setPinned($post, (bool) $validated['is_pinned'], $actor);
        }

        if (array_key_exists('comments_enabled', $validated) && $validated['comments_enabled'] !== null) {
            $this->moderation->setCommentsEnabled($post, (bool) $validated['comments_enabled'], $actor);
        }

        return back()->with('success', __('admin.community.moderated'));
    }

    public function updateComment(Request $request, Comment $comment): RedirectResponse
    {
        $this->authorize('moderate', Post::class);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', CommentStatus::values())],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $actor */
        $actor = $request->user();

        $this->moderation->setCommentStatus(
            $comment,
            CommentStatus::from($validated['status']),
            $actor,
            $validated['reason'] ?? null,
        );

        return back()->with('success', __('admin.community.moderated'));
    }

    /**
     * Close a report, resolved or dismissed.
     *
     * Acting on the content is a SEPARATE call. Closing a report and hiding a
     * post are different decisions — most reports end with the moderator
     * deciding the post was fine — and collapsing them into one button would
     * make "I looked and it was fine" the harder path.
     */
    public function resolveReport(Request $request, ContentReport $report): RedirectResponse
    {
        $this->authorize('resolve', $report);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', [
                ReportStatus::Reviewing->value,
                ReportStatus::Resolved->value,
                ReportStatus::Dismissed->value,
            ])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $actor */
        $actor = $request->user();

        $this->moderation->resolveReport(
            $report,
            ReportStatus::from($validated['status']),
            $actor,
            $validated['note'] ?? null,
        );

        return back()->with('success', __('admin.community.report_closed'));
    }

    /**
     * @return array<string, mixed>
     */
    private function postRow(Post $post): array
    {
        return [
            'ulid' => $post->ulid,
            'title' => $post->title,
            'excerpt' => Str::limit($post->body, 220),
            'category' => $post->category->value,
            'category_label' => $post->category->label(),
            'batch' => $post->batch?->name,
            'status' => $post->status->value,
            'status_label' => $post->status->label(),
            'is_pinned' => $post->is_pinned,
            'comments_enabled' => $post->comments_enabled,
            'comments_count' => $post->comments_count,
            'reactions_count' => $post->reactions_count,
            'open_reports_count' => $post->open_reports_count,
            'deleted' => $post->trashed(),
            'author' => $post->author->full_name,
            'author_ulid' => $post->author->ulid,
            'created_at' => $post->created_at?->toIso8601String(),
            'url' => route('community.show', $post->ulid, absolute: false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reportRow(ContentReport $report): array
    {
        $item = $report->reportable;

        return [
            'id' => $report->id,
            'reason' => $report->reason->value,
            'reason_label' => $report->reason->label(),
            'note' => $report->note,
            'status' => $report->status->value,
            'status_label' => $report->status->label(),
            'reporter' => $report->reporter?->full_name,
            'resolver' => $report->resolver?->name,
            'resolved_at' => $report->resolved_at?->toIso8601String(),
            'resolution_note' => $report->resolution_note,
            'created_at' => $report->created_at?->toIso8601String(),
            'item' => match (true) {
                $item instanceof Post => [
                    'kind' => 'post',
                    'id' => $item->id,
                    'ulid' => $item->ulid,
                    'excerpt' => Str::limit($item->title ?? $item->body, 220),
                    'status' => $item->status->value,
                    'author' => $item->author->full_name,
                    'url' => route('community.show', $item->ulid, absolute: false),
                ],
                $item instanceof Comment => [
                    'kind' => 'comment',
                    'id' => $item->id,
                    'ulid' => null,
                    'excerpt' => Str::limit($item->body, 220),
                    'status' => $item->status->value,
                    'author' => $item->author->full_name,
                    'url' => $this->commentUrl($item),
                ],
                // A report whose target has been hard-deleted still shows: the
                // decision a moderator made about it is part of the record.
                default => null,
            },
        ];
    }

    private function commentUrl(Comment $comment): ?string
    {
        $post = $comment->commentable;

        return $post instanceof Post
            ? route('community.show', $post->ulid, absolute: false)
            : null;
    }
}
