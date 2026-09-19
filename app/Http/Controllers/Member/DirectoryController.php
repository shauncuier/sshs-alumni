<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Enums\BloodGroup;
use App\Enums\RelationType;
use App\Http\Controllers\Controller;
use App\Http\Resources\DirectoryMemberResource;
use App\Models\Batch;
use App\Models\Member;
use App\Services\Membership\MemberSearch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The alumni directory — approved members only.
 *
 * Every row is serialised by DirectoryMemberResource, which omits any field the
 * member has hidden. A member with `show_profile = false` is excluded from the
 * query entirely, so they do not appear as an empty card either.
 *
 * @see docs/05-modules.md section 3
 */
class DirectoryController extends Controller
{
    public function __construct(
        private readonly MemberSearch $search,
    ) {}

    public function index(Request $request): Response
    {
        $isSuperAdmin = (bool) $request->user()?->hasRole('Super Admin');

        $query = Member::query()
            ->with(['batch', 'privacy'])
            ->orderBy('full_name');

        if (! $isSuperAdmin) {
            $query->directoryVisible();
        }

        $members = $this->search
            ->apply($query, $request)
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('member/directory', [
            'members' => DirectoryMemberResource::collection($members),
            'filters' => $this->search->active($request),
            'options' => [
                'batches' => Batch::query()
                    ->orderByDesc('ssc_year')
                    ->get(['id', 'name', 'ssc_year'])
                    ->map(fn (Batch $batch): array => [
                        'value' => (string) $batch->id,
                        'label' => $batch->name,
                    ]),
                'relation_types' => RelationType::options(),
                'blood_groups' => BloodGroup::options(),
                // Built from the visible members themselves, so a filter can
                // never offer a value that returns nothing.
                'districts' => $this->distinctValues('district', $isSuperAdmin),
                'industries' => $this->distinctValues('industry', $isSuperAdmin),
                'countries' => $this->distinctValues('country', $isSuperAdmin),
                'occupations' => $this->distinctValues('occupation', $isSuperAdmin),
            ],
        ]);
    }

    /**
     * The distinct values of one column across directory-visible members.
     *
     * @return array<int, string>
     */
    private function distinctValues(string $column, bool $isSuperAdmin = false): array
    {
        $query = Member::query();

        if (! $isSuperAdmin) {
            $query->directoryVisible();
        }

        /** @var array<int, string> $values */
        $values = $query
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return $values;
    }

    /**
     * One member's directory profile.
     *
     * Bound by ULID, so profiles cannot be walked by incrementing an id.
     */
    public function show(Request $request, Member $member): Response
    {
        // A member who has hidden their profile is a 404, not a 403: an
        // existence-revealing error is itself a small disclosure.
        // Super Admin has unrestricted access to view all member profiles.
        $isSuperAdmin = (bool) $request->user()?->hasRole('Super Admin');

        abort_unless(
            $isSuperAdmin || ($member->isApproved() && $member->privacy?->show_profile),
            404,
        );

        $member->load(['batch', 'privacy', 'links']);

        return Inertia::render('member/directory-show', [
            'member' => DirectoryMemberResource::make($member),
        ]);
    }
}
