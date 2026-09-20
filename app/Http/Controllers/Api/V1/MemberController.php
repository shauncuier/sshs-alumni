<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminMemberResource;
use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin-scoped member API — list and detail.
 *
 * Requires `admin.access` + `members.view`. A Batch Coordinator is
 * narrowed to their own batch by MemberPolicy.
 *
 * @see docs/10-api.md section 3
 */
class MemberController extends Controller
{
    /**
     * Paginated member list with search and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $batchId = $request->query('batch_id');

        $members = Member::query()
            ->with(['batch', 'privacy', 'user'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $needle = '%'.$search.'%';
                $query->where('search_blob', 'like', $needle);
            })
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($batchId !== null, fn (Builder $query) => $query->where('batch_id', $batchId))
            ->latest('id')
            ->paginate(min(50, max(1, (int) $request->query('per_page', 20))));

        return response()->json([
            'members' => AdminMemberResource::collection($members)->response()->getData(true),
        ]);
    }

    /**
     * Full member detail for admin review.
     */
    public function show(Member $member): JsonResponse
    {
        $member->load([
            'batch',
            'privacy',
            'links',
            'verifications.actor',
            'user',
            'verifier',
        ]);

        return response()->json([
            'member' => AdminMemberResource::make($member)->resolve(),
        ]);
    }
}
