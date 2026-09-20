<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\SponsorKind;
use App\Enums\SponsorStatus;
use App\Enums\SponsorTier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SponsorRequest;
use App\Models\Event;
use App\Models\Sponsor;
use App\Models\SponsorshipPackage;
use App\Services\Payments\PaymentRecorder;
use App\Support\Paginated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sponsors and sponsorship packages.
 *
 * Sponsors are invoiced before they pay, which is why `raise()` issues an
 * invoice number and `recordManual()` issues the receipt later. Both land in
 * the same ledger as every other kind of money.
 *
 * `is_public` controls the sponsor wall and defaults to FALSE: a sponsor
 * appears in public because somebody decided they should, not because a row
 * was created.
 *
 * @see docs/09-payments.md section 5
 */
class SponsorController extends Controller
{
    public function __construct(
        private readonly PaymentRecorder $recorder,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Sponsor::class);

        $status = (string) $request->query('status', '');

        $sponsors = Sponsor::query()
            ->with(['package', 'event'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('display_order')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('admin/sponsors/index', [
            'sponsors' => Paginated::from($sponsors, fn (Sponsor $sponsor): array => $this->row($sponsor)),
            'filters' => ['status' => $status === '' ? null : $status],
            'packages' => SponsorshipPackage::query()
                ->orderBy('display_order')
                ->get()
                ->map(fn (SponsorshipPackage $package): array => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'tier' => $package->tier->value,
                    'tier_label' => $package->tier->label(),
                    'amount' => $package->amount === null ? null : (float) $package->amount,
                    'currency' => $package->currency,
                    'benefits' => $package->benefits,
                    'max_slots' => $package->max_slots,
                    'is_active' => $package->is_active,
                    // How many slots are actually taken, so the committee can
                    // see a tier is full before promising it to somebody.
                    'taken' => $package->sponsors()
                        ->whereIn('status', [SponsorStatus::Confirmed, SponsorStatus::Paid])
                        ->count(),
                ])
                ->all(),
            'options' => [
                'statuses' => SponsorStatus::options(),
                'tiers' => SponsorTier::options(),
                'kinds' => SponsorKind::options(),
                'events' => Event::query()
                    ->orderByDesc('id')
                    ->get(['id', 'title'])
                    ->map(fn (Event $event): array => [
                        'value' => (string) $event->id,
                        'label' => $event->title,
                    ])
                    ->all(),
            ],
            'can' => [
                'manage' => $request->user()?->can('sponsors.manage') ?? false,
                'record' => $request->user()?->can('payments.create') ?? false,
            ],
        ]);
    }

    public function store(SponsorRequest $request): RedirectResponse
    {
        $this->authorize('create', Sponsor::class);

        $validated = $request->validated();

        Sponsor::query()->create([
            ...$validated,
            'currency' => config('payments.currency', 'BDT'),
            'status' => SponsorStatus::Pending,
            // Not on the wall until somebody says so.
            'is_public' => false,
        ]);

        return back()->with('success', __('common.states.saved'));
    }

    public function update(SponsorRequest $request, Sponsor $sponsor): RedirectResponse
    {
        $this->authorize('update', $sponsor);

        $validated = $request->validated();

        // `paid` is set by the ledger, not by a dropdown — otherwise a sponsor
        // could read as paid with no money recorded against them.
        if (($validated['status'] ?? null) === SponsorStatus::Paid->value) {
            unset($validated['status']);
        }

        $sponsor->update($validated);

        return back()->with('success', __('common.states.saved'));
    }

    /**
     * Raise the invoice a sponsor is asked to pay against.
     */
    public function invoice(Request $request, Sponsor $sponsor): RedirectResponse
    {
        $this->authorize('update', $sponsor);

        $payment = $this->recorder->raise(
            payable: $sponsor,
            raisedBy: $request->user(),
            withInvoice: true,
        );

        return back()->with('success', __('admin.sponsors.invoiced', [
            'invoice' => $payment->invoice_no ?? '',
        ]));
    }

    /**
     * Record the money once it arrives.
     */
    public function record(Request $request, Sponsor $sponsor): RedirectResponse
    {
        $this->authorize('update', $sponsor);

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:99999999'],
            'method' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        abort_if($user === null, 403);

        $payment = $this->recorder->recordManual(
            payable: $sponsor,
            recordedBy: $user,
            amount: isset($validated['amount']) ? (float) $validated['amount'] : null,
            method: $validated['method'] ?? null,
        );

        return back()->with('success', __('admin.payments.recorded', [
            'receipt' => $payment->receipt_no ?? '',
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Sponsor $sponsor): array
    {
        return [
            'ulid' => $sponsor->ulid,
            'name' => $sponsor->name,
            'kind' => $sponsor->kind->value,
            'kind_label' => $sponsor->kind->label(),
            'contact_name' => $sponsor->contact_name,
            'contact_email' => $sponsor->contact_email,
            'contact_phone' => $sponsor->contact_phone,
            'website' => $sponsor->website,
            'logo_url' => $sponsor->logo_path === null
                ? null
                : asset('storage/'.$sponsor->logo_path),
            'amount' => $sponsor->amount === null ? null : (float) $sponsor->amount,
            'currency' => $sponsor->currency,
            'status' => $sponsor->status->value,
            'status_label' => $sponsor->status->label(),
            'is_public' => $sponsor->is_public,
            'display_order' => $sponsor->display_order,
            'package' => $sponsor->package?->name,
            'tier_label' => $sponsor->package?->tier->label(),
            'event' => $sponsor->event?->title,
            'notes' => $sponsor->notes,
        ];
    }
}
