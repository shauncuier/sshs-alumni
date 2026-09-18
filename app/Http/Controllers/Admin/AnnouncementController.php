<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AnnouncementKind;
use App\Enums\AnnouncementLevel;
use App\Enums\AudienceScope;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Batch;
use App\Models\User;
use App\Support\Paginated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Announcements and notices — one table, two kinds.
 *
 * They have an identical shape, an identical admin form and an identical
 * public rendering; the only difference is the word on the badge. Two tables
 * would have been two of everything for one string.
 *
 * ANNOUNCEMENTS EXPIRE. `starts_at` and `ends_at` are a window rather than a
 * publication date, because "registration closes on Friday" is worse than
 * useless on Saturday and expecting somebody to remember to take it down is
 * expecting the wrong thing. `Announcement::scopeLive()` holds that rule.
 *
 * @see app/Models/Announcement.php
 */
class AnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'kind' => (string) $request->query('kind', ''),
            'status' => (string) $request->query('status', ''),
        ];

        $announcements = Announcement::query()
            ->with(['batch', 'publisher'])
            ->when($filters['kind'] !== '', fn (Builder $query) => $query->where('kind', $filters['kind']))
            ->when($filters['status'] !== '', fn (Builder $query) => $query->where('status', $filters['status']))
            ->orderByDesc('is_pinned')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/announcements/index', [
            'announcements' => Paginated::from(
                $announcements,
                fn (Announcement $announcement): array => $this->row($announcement),
            ),
            'filters' => array_map(fn (string $v): ?string => $v === '' ? null : $v, $filters),
            'options' => [
                'kinds' => AnnouncementKind::options(),
                'levels' => AnnouncementLevel::options(),
                'audiences' => AudienceScope::options(),
                'statuses' => ContentStatus::options(),
                'batches' => Batch::query()
                    ->orderByDesc('ssc_year')
                    ->get(['id', 'name'])
                    ->map(fn (Batch $batch): array => [
                        'value' => (string) $batch->id,
                        'label' => $batch->name,
                    ])
                    ->all(),
            ],
            'can' => [
                'manage' => $request->user()?->can('content.manage') ?? false,
                'publish' => $request->user()?->can('content.publish') ?? false,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Announcement::query()->create([
            ...$this->validated($request),
            'status' => ContentStatus::Draft,
        ]);

        return back()->with('success', __('admin.content.saved_draft'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->validated($request));

        return back()->with('success', __('common.states.saved'));
    }

    public function publish(Request $request, Announcement $announcement): RedirectResponse
    {
        $publish = $request->boolean('publish', true);

        /** @var User $actor */
        $actor = $request->user();

        $announcement->update([
            'status' => $publish ? ContentStatus::Published : ContentStatus::Draft,
            // Who put it in front of members. Kept when it is taken down,
            // because the question afterwards is always who published it.
            'published_by' => $publish ? $actor->id : $announcement->published_by,
        ]);

        return back()->with('success', $publish
            ? __('admin.content.published')
            : __('admin.content.unpublished'));
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', __('admin.content.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'kind' => ['required', Rule::in(AnnouncementKind::values())],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'level' => ['required', Rule::in(AnnouncementLevel::values())],
            'audience' => ['required', Rule::in(AudienceScope::values())],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        // A batch announcement without a batch would be visible to nobody, and
        // a batch id on an association-wide one is noise that reads as a rule.
        if ($validated['audience'] !== AudienceScope::Batch->value) {
            $validated['batch_id'] = null;
        }

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Announcement $announcement): array
    {
        return [
            'id' => $announcement->id,
            'kind' => $announcement->kind->value,
            'kind_label' => $announcement->kind->label(),
            'title' => $announcement->title,
            'body' => $announcement->body,
            'level' => $announcement->level->value,
            'level_label' => $announcement->level->label(),
            'audience' => $announcement->audience->value,
            'audience_label' => $announcement->audience->label(),
            'batch_id' => $announcement->batch_id,
            'batch' => $announcement->batch?->name,
            'starts_at' => $announcement->starts_at?->toIso8601String(),
            'ends_at' => $announcement->ends_at?->toIso8601String(),
            'is_pinned' => $announcement->is_pinned,
            'status' => $announcement->status->value,
            'status_label' => $announcement->status->label(),
            'published_by' => $announcement->publisher?->name,
            // What a reader would see right now, which is not the same as the
            // status: a published announcement whose window has closed is off
            // the site and the list should say so.
            'is_live' => $announcement->status === ContentStatus::Published
                && ($announcement->starts_at === null || $announcement->starts_at->isPast())
                && ($announcement->ends_at === null || $announcement->ends_at->isFuture()),
        ];
    }
}
