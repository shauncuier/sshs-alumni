<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\DonationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DonationRequest;
use App\Models\CrmContact;
use App\Models\Donation;
use App\Models\Event;
use App\Models\Member;
use App\Services\Payments\PaymentRecorder;
use App\Support\Paginated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Donations.
 *
 * A donation is a payable: recording the money goes through PaymentRecorder
 * like everything else, so the donation's status and the ledger can never
 * disagree.
 *
 * @see docs/09-payments.md section 1
 */
class DonationController extends Controller
{
    public function __construct(
        private readonly PaymentRecorder $recorder,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Donation::class);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'campaign' => (string) $request->query('campaign', ''),
        ];

        $donations = Donation::query()
            ->with(['donor', 'crmContact', 'event'])
            ->when($filters['q'] !== '', fn ($query) => $query->where('donor_name', 'like', '%'.$filters['q'].'%'))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['campaign'] !== '', fn ($query) => $query->where('campaign', $filters['campaign']))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('admin/donations/index', [
            'donations' => Paginated::from($donations, fn (Donation $donation): array => $this->row($donation)),
            'filters' => array_map(fn (string $v): ?string => $v === '' ? null : $v, $filters),
            'options' => [
                'statuses' => DonationStatus::options(),
                'campaigns' => Donation::query()
                    ->whereNotNull('campaign')
                    ->distinct()
                    ->orderBy('campaign')
                    ->pluck('campaign')
                    ->all(),
                'events' => Event::query()
                    ->orderByDesc('id')
                    ->get(['id', 'title'])
                    ->map(fn (Event $event): array => [
                        'value' => (string) $event->id,
                        'label' => $event->title,
                    ])
                    ->all(),
            ],
            'totals' => Inertia::defer(fn (): array => [
                'received' => (float) Donation::query()
                    ->where('status', DonationStatus::Received)
                    ->sum('amount'),
                'pending' => (float) Donation::query()
                    ->where('status', DonationStatus::Pending)
                    ->sum('amount'),
            ]),
            'can' => [
                'manage' => $request->user()?->can('donations.manage') ?? false,
                'record' => $request->user()?->can('payments.create') ?? false,
            ],
        ]);
    }

    /**
     * Record a donation the association has received.
     *
     * Donor identity is optional in every direction: a member, a CRM contact,
     * or a bare name on an envelope. The envelope is the common case.
     */
    public function store(DonationRequest $request): RedirectResponse
    {
        $this->authorize('create', Donation::class);

        $validated = $request->validated();

        $member = isset($validated['donor_member_ulid'])
            ? Member::query()->where('ulid', $validated['donor_member_ulid'])->first()
            : null;

        $contact = isset($validated['crm_contact_ulid'])
            ? CrmContact::query()->where('ulid', $validated['crm_contact_ulid'])->first()
            : null;

        $donation = Donation::query()->create([
            'donor_member_id' => $member?->id,
            'crm_contact_id' => $contact?->id,
            'donor_name' => $validated['donor_name'],
            'donor_email' => $validated['donor_email'] ?? null,
            'donor_phone' => $validated['donor_phone'] ?? null,
            'campaign' => $validated['campaign'] ?? null,
            'event_id' => $validated['event_id'] ?? null,
            'amount' => $validated['amount'],
            'currency' => config('payments.currency', 'BDT'),
            'is_anonymous' => $validated['is_anonymous'],
            'message' => $validated['message'] ?? null,
            'status' => DonationStatus::Pending,
            // An anonymous donation never appears on the wall, whatever this
            // says. See the public donor wall.
            'is_public' => ! $validated['is_anonymous'],
        ]);

        if ($validated['received']) {
            $user = $request->user();
            abort_if($user === null, 403);

            // Marks the donation received AND writes the ledger row, in one
            // transaction. Setting the status by hand would leave the money
            // uncounted.
            $this->recorder->recordManual(
                payable: $donation,
                recordedBy: $user,
                method: $validated['method'] ?? null,
            );
        }

        return back()->with('success', __('common.states.saved'));
    }

    /**
     * Record money against a donation that was pledged earlier.
     */
    public function receive(Request $request, Donation $donation): RedirectResponse
    {
        $this->authorize('update', $donation);

        $validated = $request->validate([
            'method' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        abort_if($user === null, 403);

        $payment = $this->recorder->recordManual(
            payable: $donation,
            recordedBy: $user,
            method: $validated['method'] ?? null,
        );

        return back()->with('success', __('admin.payments.recorded', [
            'receipt' => $payment->receipt_no ?? '',
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Donation $donation): array
    {
        return [
            'ulid' => $donation->ulid,
            'donor_name' => $donation->donor_name,
            'amount' => (float) $donation->amount,
            'currency' => $donation->currency,
            'campaign' => $donation->campaign,
            'status' => $donation->status->value,
            'status_label' => $donation->status->label(),
            'is_anonymous' => $donation->is_anonymous,
            'is_public' => $donation->is_public,
            'message' => $donation->message,
            'received_at' => $donation->received_at?->toIso8601String(),
            'created_at' => $donation->created_at?->toIso8601String(),
            'member_ulid' => $donation->donor?->ulid,
            'event' => $donation->event?->title,
        ];
    }
}
