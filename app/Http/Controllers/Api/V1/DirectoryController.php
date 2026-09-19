<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DirectoryMemberResource;
use App\Models\Member;
use App\Services\Membership\MemberSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Alumni directory API with privacy enforcement.
 *
 * @see docs/10-api.md section 3
 * @see docs/08-security-privacy.md section 2
 */
class DirectoryController extends Controller
{
    public function __construct(
        private readonly MemberSearch $search,
    ) {}

    /**
     * Search and list directory-visible members.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isSuperAdmin = (bool) $user?->hasRole('Super Admin');

        $query = Member::query()
            ->with(['batch', 'privacy'])
            ->orderBy('full_name');

        if (! $isSuperAdmin) {
            $query->directoryVisible();
        }

        $members = $this->search
            ->apply($query, $request)
            ->paginate(min(100, max(1, (int) $request->query('per_page', 20))))
            ->withQueryString();

        return response()->json([
            'members' => DirectoryMemberResource::collection($members)->response()->getData(true),
            'filters' => $this->search->active($request),
        ]);
    }

    /**
     * View an individual member's directory profile.
     */
    public function show(Request $request, Member $member): JsonResponse
    {
        $user = $request->user();
        $isSuperAdmin = (bool) $user?->hasRole('Super Admin');

        abort_unless(
            $isSuperAdmin || ($member->isApproved() && $member->privacy?->show_profile),
            404,
            'Member profile not found or private.',
        );

        $member->load(['batch', 'privacy', 'links']);

        return response()->json([
            'member' => DirectoryMemberResource::make($member)->resolve(),
        ]);
    }
}
