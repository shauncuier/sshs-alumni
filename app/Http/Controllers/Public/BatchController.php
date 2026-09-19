<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\DirectoryMemberResource;
use App\Models\Batch;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public batch pages.
 *
 * AGGREGATE COUNTS ONLY. No member names, no photos, no list.
 *
 * The directory is members-only, and a public page that enumerated real alumni
 * names and batch years would hand a scraper exactly what the directory
 * withholds. The counts come from the cached column, so this page costs one
 * query regardless of how many batches exist.
 *
 * @see docs/03-routes.md section 1
 */
class BatchController extends Controller
{
    public function index(): Response
    {
        $batches = Batch::query()
            ->where('status', 'active')
            ->orderByDesc('ssc_year')
            ->get(['id', 'slug', 'name', 'ssc_year', 'members_count', 'cover_path']);

        return Inertia::render('public/batches', [
            'batches' => $batches->map(fn (Batch $batch): array => [
                'slug' => $batch->slug,
                'name' => $batch->name,
                'ssc_year' => $batch->ssc_year,
                'members_count' => $batch->members_count,
                'cover_url' => $batch->cover_path === null
                    ? null
                    : asset('storage/'.$batch->cover_path),
            ]),
            'totals' => [
                'batches' => $batches->count(),
                'members' => $batches->sum('members_count'),
            ],
        ]);
    }

    public function show(Request $request, Batch $batch): Response
    {
        $user = $request->user();
        $isSuperAdmin = (bool) $user?->hasRole('Super Admin');
        $isApprovedMember = (bool) ($user?->member?->isApproved());
        $canViewMembers = $isSuperAdmin || $isApprovedMember || (bool) $user?->can('batches.view');

        $members = null;
        $coordinators = [];

        if ($canViewMembers) {
            $batch->load(['coordinators:id,ulid,full_name,membership_no,photo_path']);

            $coordinators = $batch->coordinators
                ->map(fn (Member $coordinator): array => [
                    'ulid' => $coordinator->ulid,
                    'name' => $coordinator->full_name,
                    'membership_no' => $coordinator->membership_no,
                    'photo_url' => $coordinator->photo_path === null
                        ? null
                        : asset('storage/'.$coordinator->photo_path),
                ])
                ->all();

            $query = Member::query()
                ->where('batch_id', $batch->id)
                ->with(['batch', 'privacy']);

            if (! $isSuperAdmin && ! (bool) $user?->can('members.view')) {
                $query->directoryVisible()
                    ->whereHas('privacy', fn ($q) => $q->where('show_in_batch_list', true));
            }

            $members = $query->orderBy('full_name')->paginate(24)->withQueryString();
        }

        return Inertia::render('public/batch-show', [
            'batch' => [
                'id' => $batch->id,
                'slug' => $batch->slug,
                'name' => $batch->name,
                'description' => $batch->description,
                'ssc_year' => $batch->ssc_year,
                'members_count' => $batch->members_count,
                'cover_url' => $batch->cover_path === null
                    ? null
                    : asset('storage/'.$batch->cover_path),
            ],
            'can_view_members' => $canViewMembers,
            'is_super_admin' => $isSuperAdmin,
            'members' => $members !== null ? DirectoryMemberResource::collection($members) : null,
            'coordinators' => $coordinators,
            'admin_batch_url' => ($isSuperAdmin || (bool) $user?->can('batches.edit'))
                ? route('admin.batches.show', $batch, absolute: false)
                : null,
        ]);
    }
}
