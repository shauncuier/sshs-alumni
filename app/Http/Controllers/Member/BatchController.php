<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\DirectoryMemberResource;
use App\Models\Batch;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A member's own cohort.
 *
 * The list is the directory narrowed to one batch, with the same privacy
 * filtering — `show_in_batch_list` is an ADDITIONAL opt-out on top of
 * `show_profile`, so a member can stay in the directory while keeping out of
 * their batch's roll call.
 *
 * The count is the batch's counter cache, not the size of the list: a member
 * who has hidden themselves is still part of the cohort, and reporting a
 * smaller number would make the batch look emptier than it is.
 *
 * @see docs/05-modules.md section 2
 */
class BatchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $member = $request->user()?->member;

        abort_if($member === null, 404);

        $batch = $member->batch;

        if ($batch === null) {
            return Inertia::render('member/batch', [
                'batch' => null,
                'members' => null,
                'coordinators' => [],
            ]);
        }

        $members = Member::query()
            ->directoryVisible()
            ->where('batch_id', $batch->id)
            ->whereHas('privacy', fn ($query) => $query->where('show_in_batch_list', true))
            ->with(['batch', 'privacy'])
            ->orderBy('full_name')
            ->paginate(24)
            ->withQueryString();

        $batch->load(['coordinators:id,ulid,full_name,membership_no,photo_path']);

        return Inertia::render('member/batch', [
            'batch' => [
                'slug' => $batch->slug,
                'name' => $batch->name,
                'description' => $batch->description,
                'ssc_year' => $batch->ssc_year,
                'members_count' => $batch->members_count,
                'cover_url' => $batch->cover_path === null
                    ? null
                    : asset('storage/'.$batch->cover_path),
            ],
            'members' => DirectoryMemberResource::collection($members),
            // Coordinators are shown whatever their own list preference: the
            // batch needs someone reachable, and accepting the role is a
            // public act within the cohort.
            'coordinators' => $batch->coordinators
                ->map(fn (Member $coordinator): array => [
                    'ulid' => $coordinator->ulid,
                    'name' => $coordinator->full_name,
                    'membership_no' => $coordinator->membership_no,
                    'photo_url' => $coordinator->photo_path === null
                        ? null
                        : asset('storage/'.$coordinator->photo_path),
                ])
                ->all(),
        ]);
    }
}
