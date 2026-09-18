<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\StoryStatus;
use App\Http\Controllers\Controller;
use App\Models\AlumniStory;
use App\Models\User;
use App\Support\Paginated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Alumni stories, and the queue that reviews them.
 *
 * A story is member-submitted and lands as `pending`. Nobody's account of
 * their own life goes onto the association's website without somebody reading
 * it first — not because members are suspected of anything, but because the
 * site speaks for the association and an unreviewed submission speaks for it
 * too.
 *
 * REJECTION IS NOT DELETION. A rejected story keeps its row and its text, so
 * a member who asks why it was not published can be answered, and so a
 * decision made in five minutes can be reversed in five minutes.
 *
 * A moderator does not rewrite the member's account; they publish it, hold it,
 * or decline it. The only editable fields here are the association's own
 * framing — the featured flag and the SEO description.
 *
 * @see docs/05-modules.md section 12
 */
class StoryController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', StoryStatus::Pending->value);

        $stories = AlumniStory::query()
            ->with(['member', 'batch', 'reviewer'])
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            // Oldest first: a queue worked newest-first leaves the story
            // nobody got to at the bottom forever.
            ->orderBy('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/stories/index', [
            'stories' => Paginated::from($stories, fn (AlumniStory $story): array => $this->row($story)),
            'filters' => ['status' => $status],
            'options' => ['statuses' => StoryStatus::options()],
            'can' => [
                'manage' => $request->user()?->can('content.manage') ?? false,
                'publish' => $request->user()?->can('content.publish') ?? false,
            ],
        ]);
    }

    /**
     * Publish, hold or decline.
     *
     * Publishing stamps `published_at` once and keeps it: a story taken down
     * and put back is the same story, first published on the day it first
     * appeared.
     */
    public function review(Request $request, AlumniStory $story): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(StoryStatus::values())],
        ]);

        /** @var User $actor */
        $actor = $request->user();

        $status = StoryStatus::from($validated['status']);

        $story->forceFill([
            'status' => $status,
            'reviewed_by' => $actor->id,
            'published_at' => $status === StoryStatus::Published
                ? ($story->published_at ?? now())
                : $story->published_at,
        ])->save();

        return back()->with('success', __('admin.stories.reviewed'));
    }

    /**
     * The association's own framing, not the member's words.
     */
    public function update(Request $request, AlumniStory $story): RedirectResponse
    {
        $story->update($request->validate([
            'is_featured' => ['nullable', 'boolean'],
            'career_summary' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]));

        return back()->with('success', __('common.states.saved'));
    }

    /**
     * @return array<string, mixed>
     */
    private function row(AlumniStory $story): array
    {
        return [
            'id' => $story->id,
            'slug' => $story->slug,
            'title' => $story->title,
            'body' => $story->body,
            'excerpt' => Str::limit(strip_tags($story->body), 240),
            'author_name' => $story->author_name,
            'member_ulid' => $story->member?->ulid,
            'batch' => $story->batch?->name,
            'career_summary' => $story->career_summary,
            'is_featured' => $story->is_featured,
            'status' => $story->status->value,
            'status_label' => $story->status->label(),
            'published_at' => $story->published_at?->toIso8601String(),
            'submitted_at' => $story->created_at?->toIso8601String(),
            'reviewer' => $story->reviewer?->name,
            'photo_url' => $story->photo_path === null ? null : asset('storage/'.$story->photo_path),
            'meta_description' => $story->meta_description,
            'url' => $story->status === StoryStatus::Published
                ? route('stories.show', $story->slug, absolute: false)
                : null,
        ];
    }
}
