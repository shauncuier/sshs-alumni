<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BatchStatus;
use App\Enums\MemberStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BatchRequest;
use App\Http\Resources\AdminBatchResource;
use App\Models\Batch;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Batch administration.
 *
 * `members_count` is read from the counter cache rather than counted, so this
 * list costs one query no matter how many batches exist.
 *
 * A Batch Coordinator holds `batches.edit` but BatchPolicy narrows them to the
 * batches they actually coordinate — the list below still shows every batch,
 * because knowing that SSC 1994 exists discloses nothing.
 *
 * @see docs/05-modules.md section 2
 */
class BatchController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Batch::class);

        $term = trim((string) $request->query('q', ''));

        $batches = Batch::query()
            ->with(['coordinators:id,ulid,full_name,membership_no,photo_path'])
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($inner) use ($term): void {
                    $inner->where('name', 'like', '%'.$term.'%')
                        ->orWhere('ssc_year', 'like', $term.'%');
                });
            })
            ->orderByDesc('ssc_year')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('admin/batches/index', [
            'batches' => AdminBatchResource::collection($batches),
            'filters' => ['q' => $term === '' ? null : $term],
            'options' => [
                'statuses' => BatchStatus::options(),
            ],
            'can' => [
                'create' => $request->user()?->can('create', Batch::class) ?? false,
            ],
        ]);
    }

    public function show(Request $request, Batch $batch): Response
    {
        $this->authorize('view', $batch);

        $batch->load(['coordinators:id,ulid,full_name,membership_no,photo_path']);

        return Inertia::render('admin/batches/show', [
            'batch' => [
                ...AdminBatchResource::make($batch)->resolve(),
                'description' => $batch->description,
                'cover_url' => $batch->cover_path === null
                    ? null
                    : asset('storage/'.$batch->cover_path),
            ],
            // Only approved members of THIS batch can coordinate it. A
            // coordinator from another cohort would be given reach over
            // records they have no relationship to.
            'candidates' => Member::query()
                ->where('batch_id', $batch->id)
                ->where('status', MemberStatus::Approved)
                ->whereNotIn('id', $batch->coordinators->pluck('id'))
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'membership_no'])
                ->map(fn (Member $member): array => [
                    'value' => (string) $member->id,
                    'label' => $member->membership_no === null
                        ? $member->full_name
                        : $member->full_name.' — '.$member->membership_no,
                ])
                ->all(),
            'options' => [
                'statuses' => BatchStatus::options(),
            ],
            'can' => [
                'update' => $request->user()?->can('update', $batch) ?? false,
            ],
        ]);
    }

    public function store(BatchRequest $request): RedirectResponse
    {
        $this->authorize('create', Batch::class);

        $batch = Batch::query()->create([
            ...$request->validated(),
            'slug' => $this->slugFor($request->integer('ssc_year')),
        ]);

        return to_route('admin.batches.show', $batch)
            ->with('success', __('common.states.saved'));
    }

    public function update(BatchRequest $request, Batch $batch): RedirectResponse
    {
        $this->authorize('update', $batch);

        $validated = $request->validated();

        // The slug encodes the SSC year, so a corrected year has to carry the
        // slug with it or the URL starts lying about the cohort.
        if ((int) $validated['ssc_year'] !== $batch->ssc_year) {
            $validated['slug'] = $this->slugFor((int) $validated['ssc_year']);
        }

        $batch->update($validated);

        return back()->with('success', __('common.states.saved'));
    }

    /**
     * Assign a coordinator.
     *
     * `syncWithoutDetaching` rather than `attach`, because a double-submitted
     * form would otherwise hit the unique constraint and show the committee a
     * database error for what is a no-op.
     */
    public function addCoordinator(Request $request, Batch $batch): RedirectResponse
    {
        $this->authorize('update', $batch);

        $validated = $request->validate([
            'member_id' => [
                'required',
                'integer',
                // Scoped to this batch in the query, not just validated as an
                // existing member.
                'exists:members,id',
            ],
        ]);

        $member = Member::query()
            ->where('id', $validated['member_id'])
            ->where('batch_id', $batch->id)
            ->where('status', MemberStatus::Approved)
            ->firstOrFail();

        $batch->coordinators()->syncWithoutDetaching([
            $member->id => [
                'assigned_at' => now(),
                'assigned_by' => $request->user()?->id,
            ],
        ]);

        return back()->with('success', __('admin.batches.coordinator_added'));
    }

    public function removeCoordinator(Batch $batch, Member $member): RedirectResponse
    {
        $this->authorize('update', $batch);

        $batch->coordinators()->detach($member->id);

        return back()->with('success', __('admin.batches.coordinator_removed'));
    }

    private function slugFor(int $sscYear): string
    {
        $slug = Str::slug('ssc-'.$sscYear);

        // The SSC year is unique, so the slug is too — but a soft-deleted
        // batch still holds its slug, and the unique index ignores deletion.
        $suffix = 1;
        $candidate = $slug;

        while (Batch::withTrashed()->where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.++$suffix;
        }

        return $candidate;
    }
}
