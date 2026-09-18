<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Enums\MediaCollection;
use App\Enums\StoryStatus;
use App\Http\Controllers\Controller;
use App\Models\AlumniStory;
use App\Models\Member;
use App\Services\Media\MediaService;
use App\Support\SlugFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A member telling their own story.
 *
 * Submitted as `pending` and read by somebody before it appears — not because
 * members are suspected of anything, but because the site speaks for the
 * association and an unreviewed submission speaks for it too.
 *
 * A MEMBER SEES THEIR OWN STORY WHATEVER ITS STATE, including a rejected one,
 * and including the reviewer's decision. A submission that silently never
 * appears is how people conclude they were ignored.
 *
 * Editing is allowed while it is pending and stops once it is published: the
 * version somebody reviewed and the version on the site have to be the same
 * one.
 *
 * @see app/Http/Controllers/Admin/StoryController.php
 */
class StoryController extends Controller
{
    public function __construct(
        private readonly MediaService $media,
    ) {}

    public function index(Request $request): Response
    {
        /** @var Member $member */
        $member = $request->user()?->member;

        $stories = AlumniStory::query()
            ->where('member_id', $member->id)
            ->orderByDesc('id')
            ->get();

        return Inertia::render('member/stories', [
            'stories' => $stories
                ->map(fn (AlumniStory $story): array => [
                    'id' => $story->id,
                    'title' => $story->title,
                    'body' => $story->body,
                    'career_summary' => $story->career_summary,
                    'status' => $story->status->value,
                    'status_label' => $story->status->label(),
                    'submitted_at' => $story->created_at?->toIso8601String(),
                    'published_at' => $story->published_at?->toIso8601String(),
                    'photo_url' => $story->photo_path === null
                        ? null
                        : asset('storage/'.$story->photo_path),
                    'editable' => $story->status === StoryStatus::Pending,
                    'url' => $story->status === StoryStatus::Published
                        ? route('stories.show', $story->slug, absolute: false)
                        : null,
                ])
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        /** @var Member $member */
        $member = $request->user()?->member;

        $story = AlumniStory::query()->create([
            ...$validated,
            'member_id' => $member->id,
            // The name the association knows them by, not a field they type.
            // A story bylined to somebody other than its author is exactly the
            // thing a review queue exists to prevent.
            'author_name' => $member->full_name,
            'batch_id' => $member->batch_id,
            // Slugged at submission because the column is UNIQUE and NOT NULL;
            // two pending stories cannot both hold an empty one.
            'slug' => SlugFactory::unique(AlumniStory::class, $validated['title'], 'story'),
            'status' => StoryStatus::Pending,
        ]);

        $this->attachPhoto($request, $story);

        return back()->with('success', __('member.stories.submitted'));
    }

    public function update(Request $request, AlumniStory $story): RedirectResponse
    {
        $this->authoriseOwn($request, $story);

        // Published is final for the author. The version somebody reviewed and
        // the version on the site have to be the same one.
        abort_unless($story->status === StoryStatus::Pending, 403, __('member.stories.locked'));

        $story->update($this->validated($request));

        $this->attachPhoto($request, $story);

        return back()->with('success', __('common.states.saved'));
    }

    public function destroy(Request $request, AlumniStory $story): RedirectResponse
    {
        $this->authoriseOwn($request, $story);

        abort_unless($story->status === StoryStatus::Pending, 403, __('member.stories.locked'));

        $story->delete();

        return back()->with('success', __('member.stories.withdrawn'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'min:200', 'max:20000'],
            'career_summary' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function authoriseOwn(Request $request, AlumniStory $story): void
    {
        $member = $request->user()?->member;

        abort_unless($member !== null && $story->member_id === $member->id, 403);
    }

    private function attachPhoto(Request $request, AlumniStory $story): void
    {
        $photo = $request->file('photo');

        if (! $photo instanceof UploadedFile) {
            return;
        }

        $request->validate([
            'photo' => $this->media->validationRules(MediaCollection::Profile),
        ]);

        $media = $this->media->store($photo, MediaCollection::Profile, $story);

        $story->update(['photo_path' => $media->path]);
    }
}
