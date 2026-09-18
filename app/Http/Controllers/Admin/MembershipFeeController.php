<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\FeeStatus;
use App\Enums\MemberStatus;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MembershipFee;
use App\Services\Payments\PaymentRecorder;
use App\Support\Paginated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Membership fees.
 *
 * WAIVING IS NOT PAYING. A volunteer-run association waives fees routinely —
 * for a founding member, someone in hardship, a teacher. Recording that as a
 * payment would put money in the ledger that nobody ever received, so a waiver
 * sets `waived` and never touches `payments`.
 *
 * @see docs/09-payments.md section 1
 */
class MembershipFeeController extends Controller
{
    public function __construct(
        private readonly PaymentRecorder $recorder,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', MembershipFee::class);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'period' => (string) $request->query('period', ''),
        ];

        $fees = MembershipFee::query()
            ->with('member')
            ->when($filters['q'] !== '', fn ($query) => $query->whereHas(
                'member',
                fn ($inner) => $inner->where('full_name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('membership_no', 'like', '%'.$filters['q'].'%'),
            ))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['period'] !== '', fn ($query) => $query->where('period_label', $filters['period']))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('admin/fees/index', [
            'fees' => Paginated::from($fees, fn (MembershipFee $fee): array => $this->row($fee)),
            'filters' => array_map(fn (string $v): ?string => $v === '' ? null : $v, $filters),
            'options' => [
                'statuses' => FeeStatus::options(),
                'periods' => MembershipFee::query()
                    ->distinct()
                    ->orderByDesc('period_label')
                    ->pluck('period_label')
                    ->all(),
            ],
            'totals' => Inertia::defer(fn (): array => [
                'collected' => (float) MembershipFee::query()
                    ->where('status', FeeStatus::Paid)
                    ->sum('amount'),
                'outstanding' => (float) MembershipFee::query()
                    ->where('status', FeeStatus::Pending)
                    ->sum('amount'),
                'waived' => (float) MembershipFee::query()
                    ->where('waived', true)
                    ->sum('amount'),
            ]),
            'can' => [
                'manage' => $request->user()?->can('payments.create') ?? false,
            ],
        ]);
    }

    /**
     * Raise a fee for every approved member for one period.
     *
     * Idempotent by the unique index on (member_id, period_label): running it
     * twice adds the members who joined in between and leaves the rest alone.
     */
    public function generate(Request $request): RedirectResponse
    {
        $this->authorize('create', MembershipFee::class);

        $validated = $request->validate([
            'period_label' => ['required', 'string', 'max:40'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'due_at' => ['nullable', 'date'],
        ]);

        $created = DB::transaction(function () use ($validated): int {
            $members = Member::query()
                ->where('status', MemberStatus::Approved)
                ->whereDoesntHave(
                    'membershipFees',
                    fn ($query) => $query->where('period_label', $validated['period_label']),
                )
                ->get(['id']);

            foreach ($members as $member) {
                MembershipFee::query()->create([
                    'member_id' => $member->id,
                    'period_label' => $validated['period_label'],
                    'amount' => $validated['amount'],
                    'currency' => config('payments.currency', 'BDT'),
                    'due_at' => $validated['due_at'] ?? null,
                    'status' => FeeStatus::Pending,
                ]);
            }

            return $members->count();
        });

        // trans_choice, not __(): the string carries plural forms and __()
        // would return the whole pipe-separated line.
        return back()->with('success', trans_choice('admin.fees.generated', $created, ['count' => $created]));
    }

    /**
     * Record a fee payment.
     */
    public function pay(Request $request, MembershipFee $fee): RedirectResponse
    {
        $this->authorize('update', $fee);

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:9999999'],
            'method' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        abort_if($user === null, 403);

        $payment = $this->recorder->recordManual(
            payable: $fee,
            recordedBy: $user,
            amount: isset($validated['amount']) ? (float) $validated['amount'] : null,
            method: $validated['method'] ?? null,
        );

        return back()->with('success', __('admin.payments.recorded', [
            'receipt' => $payment->receipt_no ?? '',
        ]));
    }

    /**
     * Waive a fee.
     *
     * NOT a payment. No ledger row, no receipt — the money was never received
     * and the accounts must not claim it was. The reason is required, because
     * a waiver with no reason is indistinguishable from an oversight.
     */
    public function waive(Request $request, MembershipFee $fee): RedirectResponse
    {
        $this->authorize('update', $fee);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $fee->update([
            'waived' => true,
            'waived_reason' => $validated['reason'],
            'status' => FeeStatus::Waived,
        ]);

        return back()->with('success', __('admin.fees.waived'));
    }

    /**
     * @return array<string, mixed>
     */
    private function row(MembershipFee $fee): array
    {
        return [
            'id' => $fee->id,
            'period_label' => $fee->period_label,
            'amount' => (float) $fee->amount,
            'currency' => $fee->currency,
            'due_at' => $fee->due_at?->toDateString(),
            'status' => $fee->status->value,
            'status_label' => $fee->status->label(),
            'waived' => $fee->waived,
            'waived_reason' => $fee->waived_reason,
            'is_overdue' => $fee->due_at !== null
                && $fee->due_at->isPast()
                && $fee->status === FeeStatus::Pending,
            'member' => [
                'ulid' => $fee->member?->ulid,
                'full_name' => $fee->member?->full_name,
                'membership_no' => $fee->member?->membership_no,
            ],
        ];
    }
}
