<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\DonationStatus;
use App\Enums\SponsorStatus;
use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Sponsor;
use App\Models\SponsorshipPackage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public giving pages: how to donate, and who already has.
 *
 * No online payment is taken here. The association collects in cash, by
 * transfer and through mobile financial services settled outside the platform,
 * so this page tells people how to give and the office records it. Pretending
 * to take a card would be worse than honest instructions.
 *
 * THE DONOR WALL IS OPT-IN TWICE OVER. A donation appears only when it is
 * received, NOT anonymous, AND marked public. Anonymous wins over everything:
 * somebody who asked not to be named must not be named because a checkbox
 * elsewhere said otherwise.
 *
 * @see docs/09-payments.md section 3
 */
class GivingController extends Controller
{
    public function donate(): Response
    {
        return Inertia::render('public/donate', [
            // Names and amounts only. No email, no phone, no message that was
            // not written for the wall.
            'recent' => Donation::query()
                ->where('status', DonationStatus::Received)
                ->where('is_anonymous', false)
                ->where('is_public', true)
                ->latest('received_at')
                ->limit(20)
                ->get()
                ->map(fn (Donation $donation): array => [
                    'donor_name' => $donation->donor_name,
                    'amount' => (float) $donation->amount,
                    'currency' => $donation->currency,
                    'campaign' => $donation->campaign,
                    'received_at' => $donation->received_at?->toDateString(),
                ])
                ->all(),

            'total' => Inertia::defer(fn (): array => [
                'amount' => (float) Donation::query()
                    ->where('status', DonationStatus::Received)
                    ->sum('amount'),
                'donors' => Donation::query()
                    ->where('status', DonationStatus::Received)
                    ->count(),
                'currency' => (string) config('payments.currency', 'BDT'),
            ]),
        ]);
    }

    public function sponsorship(): Response
    {
        return Inertia::render('public/sponsorship', [
            'packages' => SponsorshipPackage::query()
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get()
                ->map(fn (SponsorshipPackage $package): array => [
                    'slug' => $package->slug,
                    'name' => $package->name,
                    'tier' => $package->tier->value,
                    'tier_label' => $package->tier->label(),
                    'amount' => $package->amount === null ? null : (float) $package->amount,
                    'currency' => $package->currency,
                    'benefits' => $package->benefits,
                    // Whether any remain, never how many — the same rule the
                    // event ticket types follow.
                    'available' => $package->max_slots === null
                        || $package->sponsors()
                            ->whereIn('status', [SponsorStatus::Confirmed, SponsorStatus::Paid])
                            ->count() < $package->max_slots,
                ])
                ->all(),

            // Confirmed sponsors who agreed to be listed.
            'sponsors' => Sponsor::query()
                ->where('is_public', true)
                ->whereIn('status', [SponsorStatus::Confirmed, SponsorStatus::Paid])
                ->with('package')
                ->orderBy('display_order')
                ->get()
                ->map(fn (Sponsor $sponsor): array => [
                    'name' => $sponsor->name,
                    'website' => $sponsor->website,
                    'logo_url' => $sponsor->logo_path === null
                        ? null
                        : asset('storage/'.$sponsor->logo_path),
                    'tier_label' => $sponsor->package?->tier->label(),
                ])
                ->all(),
        ]);
    }
}
