<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DirectoryMemberResource;
use App\Models\Batch;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Batch cohorts API.
 *
 * @see docs/10-api.md section 3
 */
class BatchController extends Controller
{
    /**
     * List all active graduation batches.
     */
    public function index(): JsonResponse
    {
        $batches = Batch::query()
            ->where('status', 'active')
            ->orderByDesc('ssc_year')
            ->get(['id', 'slug', 'name', 'ssc_year', 'members_count', 'cover_path']);

        return response()->json([
            'batches' => $batches->map(fn (Batch $batch): array => [
                'slug' => $batch->slug,
                'name' => $batch->name,
                'ssc_year' => $batch->ssc_year,
                'members_count' => $batch->members_count,
                'cover_url' => $batch->cover_path ? asset('storage/'.$batch->cover_path) : null,
            ]),
        ]);
    }

    /**
     * Details of a single cohort.
     */
    public function show(Request $request, Batch $batch): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();
        $isSuperAdmin = (bool) $user?->hasRole('Super Admin');
        $isApprovedMember = (bool) ($user?->member?->isApproved());
        $canViewMembers = $isSuperAdmin || $isApprovedMember || (bool) $user?->can('batches.view');

        $batch->load(['coordinators:id,ulid,full_name,membership_no,photo_path']);

        $coordinators = $batch->coordinators->map(fn (Member $c): array => [
            'ulid' => $c->ulid,
            'name' => $c->full_name,
            'membership_no' => $c->membership_no,
            'photo_url' => $c->photo_path ? asset('storage/'.$c->photo_path) : null,
        ]);

        $members = null;

        if ($canViewMembers) {
            $query = Member::query()
                ->where('batch_id', $batch->id)
                ->with(['batch', 'privacy']);

            if (! $isSuperAdmin && ! (bool) $user?->can('members.view')) {
                $query->directoryVisible()
                    ->whereHas('privacy', fn ($q) => $q->where('show_in_batch_list', true));
            }

            $paginated = $query->orderBy('full_name')->paginate(min(100, max(1, (int) $request->query('per_page', 24))));
            $members = DirectoryMemberResource::collection($paginated)->response()->getData(true);
        }

        return response()->json([
            'batch' => [
                'slug' => $batch->slug,
                'name' => $batch->name,
                'description' => $batch->description,
                'ssc_year' => $batch->ssc_year,
                'members_count' => $batch->members_count,
                'cover_url' => $batch->cover_path ? asset('storage/'.$batch->cover_path) : null,
            ],
            'can_view_members' => $canViewMembers,
            'coordinators' => $coordinators,
            'members' => $members,
        ]);
    }
}
