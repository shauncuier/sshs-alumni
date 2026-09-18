<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Donation;
use App\Models\EventRegistration;
use App\Models\MembershipFee;
use App\Models\Payment;
use App\Models\Sponsor;
use App\Services\Payments\Contracts\Payable;
use App\Services\Payments\PaymentRecorder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The ledger.
 *
 * Every kind of money — membership fees, event registrations, donations,
 * sponsorships — resolves through `payments.payable`, so this one list is the
 * whole income picture and no report can disagree with another.
 *
 * @see docs/09-payments.md
 */
class PaymentController extends Controller
{
    /**
     * What a payment may be recorded against. Keyed so a request names a kind
     * rather than posting a class name.
     *
     * @var array<string, class-string<Model>>
     */
    private const PAYABLES = [
        'fee' => MembershipFee::class,
        'registration' => EventRegistration::class,
        'donation' => Donation::class,
        'sponsor' => Sponsor::class,
    ];

    public function __construct(
        private readonly PaymentRecorder $recorder,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];

        $payments = Payment::query()
            ->with(['payable', 'payer', 'recorder'])
            ->when($filters['q'] !== '', fn ($query) => $query->where(function ($inner) use ($filters): void {
                $needle = '%'.$filters['q'].'%';

                $inner->where('payer_name', 'like', $needle)
                    ->orWhere('receipt_no', 'like', $needle)
                    ->orWhere('invoice_no', 'like', $needle)
                    ->orWhere('gateway_txn_id', 'like', $needle);
            }))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['from'] !== '', fn ($query) => $query->whereDate('paid_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($query) => $query->whereDate('paid_at', '<=', $filters['to']))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('admin/payments/index', [
            'payments' => PaymentResource::collection($payments),
            'filters' => array_map(fn (string $v): ?string => $v === '' ? null : $v, $filters),
            'options' => ['statuses' => PaymentStatus::options()],
            // Deferred: three aggregates over the whole ledger, and the list
            // is what the page is mostly for.
            'totals' => Inertia::defer(fn (): array => $this->totals($request)),
            'can' => [
                'record' => $request->user()?->can('create', Payment::class) ?? false,
            ],
        ]);
    }

    public function show(Request $request, Payment $payment): Response
    {
        $this->authorize('view', $payment);

        $payment->load(['payable', 'payer', 'recorder']);

        return Inertia::render('admin/payments/show', [
            'payment' => PaymentResource::make($payment),
            'can' => [
                'refund' => $request->user()?->can('refund', $payment) ?? false,
            ],
        ]);
    }

    /**
     * Record money already received.
     *
     * The six things that must happen together — ledger row, receipt number,
     * the payable's own status, an audit row, a CRM timeline entry, a
     * notification — are PaymentRecorder's job, not this controller's.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $validated = $request->validate([
            'payable_kind' => ['required', 'string', 'in:'.implode(',', array_keys(self::PAYABLES))],
            'payable_id' => ['required', 'integer'],
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:99999999'],
            'method' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:120'],
            // Money received last week is legitimate; money received next week
            // has not been received.
            'paid_at' => ['nullable', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payable = $this->resolvePayable(
            $validated['payable_kind'],
            (int) $validated['payable_id'],
        );

        $user = $request->user();
        abort_if($user === null, 403);

        $payment = $this->recorder->recordManual(
            payable: $payable,
            recordedBy: $user,
            amount: isset($validated['amount']) ? (float) $validated['amount'] : null,
            method: $validated['method'] ?? null,
            reference: $validated['reference'] ?? null,
            paidAt: isset($validated['paid_at'])
                ? new \DateTimeImmutable($validated['paid_at'])
                : null,
            notes: $validated['notes'] ?? null,
        );

        return to_route('admin.payments.show', $payment)
            ->with('success', __('admin.payments.recorded', [
                'receipt' => $payment->receipt_no ?? '',
            ]));
    }

    /**
     * Give money back.
     *
     * The original row is preserved. A ledger that erases its mistakes cannot
     * be audited.
     */
    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('refund', $payment);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        abort_if($user === null, 403);

        $this->recorder->refund($payment, $user, $validated['reason']);

        return back()->with('success', __('admin.payments.refunded'));
    }

    private function resolvePayable(string $kind, int $id): Payable&Model
    {
        /** @var class-string<Model> $class */
        $class = self::PAYABLES[$kind];

        $payable = $class::query()->findOrFail($id);

        // Belt and braces: the map only contains payables, but a future entry
        // added carelessly should fail here rather than half-record money.
        abort_unless($payable instanceof Payable, 422);

        return $payable;
    }

    /**
     * @return array{received: float, pending: float, refunded: float, currency: string}
     */
    private function totals(Request $request): array
    {
        $scope = fn () => Payment::query()
            ->when(
                filled($request->query('from')),
                fn ($query) => $query->whereDate('paid_at', '>=', $request->query('from')),
            )
            ->when(
                filled($request->query('to')),
                fn ($query) => $query->whereDate('paid_at', '<=', $request->query('to')),
            );

        return [
            'received' => (float) $scope()->where('status', PaymentStatus::Paid)->sum('amount'),
            'pending' => (float) $scope()->where('status', PaymentStatus::Pending)->sum('amount'),
            'refunded' => (float) $scope()->where('status', PaymentStatus::Refunded)->sum('amount'),
            'currency' => (string) config('payments.currency', 'BDT'),
        ];
    }
}
