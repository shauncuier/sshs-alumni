<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BloodGroup;
use App\Enums\MemberStatus;
use App\Enums\RelationType;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminMemberResource;
use App\Models\Batch;
use App\Models\Member;
use App\Services\Membership\MemberSearch;
use App\Services\Membership\VerificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function __construct(
        private readonly MemberSearch $search,
        private readonly VerificationService $verification,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Member::class);

        $query = Member::query()
            ->with(['batch'])
            ->latest('created_at');

        // A Batch Coordinator's list is narrowed to their own batch here as
        // well as in the policy — the policy protects the record, this keeps
        // the list itself from disclosing who exists outside their reach.
        $this->scopeToCoordinatedBatches($query, $request);

        $members = $this->search
            ->apply($query, $request)
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/members/index', [
            'members' => AdminMemberResource::collection($members),
            'filters' => $this->search->active($request),
            'counts' => Inertia::defer(fn (): array => $this->statusCounts($request)),
            'options' => [
                'statuses' => MemberStatus::options(),
                'relation_types' => RelationType::options(),
                'blood_groups' => BloodGroup::options(),
                'batches' => Batch::query()
                    ->orderByDesc('ssc_year')
                    ->get(['id', 'name', 'name_bn'])
                    ->map(fn (Batch $batch): array => [
                        'value' => (string) $batch->id,
                        'label' => $batch->getTranslation('name') ?? $batch->name,
                    ]),
            ],
        ]);
    }

    public function show(Member $member): Response
    {
        $this->authorize('view', $member);

        $member->load([
            'batch', 'privacy', 'links', 'user',
            'verifications' => fn ($query) => $query->with('actor')->latest('id'),
        ]);

        return Inertia::render('admin/members/show', [
            'member' => AdminMemberResource::make($member),
            'canVerify' => $this->canVerify($member),
            'allowedTransitions' => array_map(
                fn (MemberStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                $this->verification->allowedFrom($member->status),
            ),
        ]);
    }

    private function canVerify(Member $member): bool
    {
        $user = request()->user();

        return $user !== null && $user->can('verify', $member);
    }

    /**
     * @param  Builder<Member>  $query
     */
    private function scopeToCoordinatedBatches(
        Builder $query,
        Request $request,
    ): void {
        $user = $request->user();

        if ($user === null || ! $user->hasRole('Batch Coordinator')) {
            return;
        }

        // Super Admin never reaches here, because Gate::before short-circuits
        // before the role matters.
        if ($user->hasRole('Super Admin')) {
            return;
        }

        $batchIds = $user->member?->coordinatedBatches()->pluck('batches.id') ?? collect();

        $query->whereIn('batch_id', $batchIds->all());
    }

    /**
     * @return array<string, int>
     */
    private function statusCounts(Request $request): array
    {
        $query = Member::query();
        $this->scopeToCoordinatedBatches($query, $request);

        return $query
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }
}
