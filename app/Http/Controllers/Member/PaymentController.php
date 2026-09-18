<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Enums\DonationStatus;
use App\Enums\FeeStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Donation;
use App\Models\Member;
use App\Models\MembershipFee;
use App\Models\Payment;
use App\Support\Paginated;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A member's own money: what they have paid, what they owe, what they gave.
 *
 * Scoped to their own records throughout. A member reading somebody else's
 * receipt would be a worse disclosure than the directory ever risks.
 */
class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $member = $this->memberFor($request);

        $payments = Payment::query()
            ->where('payer_member_id', $member->id)
            ->with(['payable', 'recorder'])
            ->latest('id')
            ->paginate(20);

        return Inertia::render('member/payments', [
            'payments' => PaymentResource::collection($payments),

            // What is still owed. Waived fees are absent: they are settled,
            // just not by money.
            'outstanding' => MembershipFee::query()
                ->where('member_id', $member->id)
                ->where('status', FeeStatus::Pending)
                ->orderBy('due_at')
                ->get()
                ->map(fn (MembershipFee $fee): array => [
                    'id' => $fee->id,
                    'period_label' => $fee->period_label,
                    'amount' => (float) $fee->amount,
                    'currency' => $fee->currency,
                    'due_at' => $fee->due_at?->toDateString(),
                    'is_overdue' => $fee->due_at !== null && $fee->due_at->isPast(),
                ])
                ->all(),

            'totals' => [
                'paid' => (float) Payment::query()
                    ->where('payer_member_id', $member->id)
                    ->where('status', PaymentStatus::Paid)
                    ->sum('amount'),
                'currency' => (string) config('payments.currency', 'BDT'),
            ],
        ]);
    }

    public function donations(Request $request): Response
    {
        $member = $this->memberFor($request);

        $donations = Donation::query()
            ->where('donor_member_id', $member->id)
            ->latest('id')
            ->paginate(20);

        return Inertia::render('member/donations', [
            'donations' => Paginated::from($donations, fn (Donation $donation): array => [
                'ulid' => $donation->ulid,
                'amount' => (float) $donation->amount,
                'currency' => $donation->currency,
                'campaign' => $donation->campaign,
                'status' => $donation->status->value,
                'status_label' => $donation->status->label(),
                'is_anonymous' => $donation->is_anonymous,
                'received_at' => $donation->received_at?->toDateString(),
            ]),
            'total' => (float) Donation::query()
                ->where('donor_member_id', $member->id)
                ->where('status', DonationStatus::Received)
                ->sum('amount'),
            'currency' => (string) config('payments.currency', 'BDT'),
        ]);
    }

    /**
     * One receipt.
     *
     * Rendered in the browser rather than through dompdf: Bengali conjunct
     * shaping in dompdf is unreliable, and a receipt carrying the school's name
     * would be the document most likely to show it.
     */
    public function receipt(Request $request, Payment $payment): Response
    {
        $member = $this->memberFor($request);

        // 404, not 403 — an existence-revealing error is itself a small
        // disclosure.
        abort_unless($payment->payer_member_id === $member->id, 404);
        abort_unless($payment->status === PaymentStatus::Paid, 404);

        $payment->load(['payable', 'recorder']);

        return Inertia::render('member/receipt', [
            'payment' => PaymentResource::make($payment),
        ]);
    }

    private function memberFor(Request $request): Member
    {
        $member = $request->user()?->member;

        abort_if($member === null, 404);

        return $member;
    }
}
