<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\FaqGroup;
use App\Enums\SponsorStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicEventResource;
use App\Models\Event;
use App\Models\Faq;
use App\Models\SchoolMilestone;
use App\Models\Sponsor;
use App\Services\Events\EventRegistrar;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Golden Jubilee microsite.
 *
 * There is no Jubilee subsystem. Every page here resolves the FLAGSHIP EVENT —
 * one row in `events` with `is_flagship = true` — and renders it with a
 * distinct visual treatment plus microsite-only copy from the `jubilee`
 * settings group.
 *
 * `is_flagship` can move to a future event and the microsite follows the flag.
 * Nothing here becomes dead code on 1 January 2027.
 *
 * THE DATE RULE: the event's date is serialised only when `date_status` is
 * `announced`. Until then the page renders the "to be announced" line and the
 * countdown is not rendered at all.
 *
 * @see docs/17-golden-jubilee.md
 */
class JubileeController extends Controller
{
    public function __construct(
        private readonly EventRegistrar $registrar,
    ) {}

    public function index(): Response
    {
        $event = $this->flagship();

        return Inertia::render('jubilee/index', [
            'event' => $event === null ? null : PublicEventResource::make($event->load('ticketTypes')),
            'registration' => $event === null ? null : [
                'open' => $this->registrar->isOpen($event),
                'full' => $this->registrar->seatsLeft($event) === 0,
            ],
            // Anchored by the school's founding year and the jubilee year,
            // both read from settings rather than hard-coded.
            // Deferred: the landing page renders its hero immediately and
            // these three arrive on a second request.
            'milestones' => Inertia::defer(fn (): array => SchoolMilestone::query()
                ->orderBy('year')
                ->orderBy('display_order')
                ->get()
                ->map(fn (SchoolMilestone $milestone): array => [
                    'year' => $milestone->year,
                    'date_label' => $milestone->date_label,
                    'title' => $milestone->title,
                    'description' => $milestone->description,
                    'image_url' => $milestone->image_path === null
                        ? null
                        : asset('storage/'.$milestone->image_path),
                    'is_highlighted' => $milestone->is_highlighted,
                ])
                ->all()),
            'faqs' => Inertia::defer(fn (): array => $this->faqs()),
            'sponsors' => Inertia::defer(
                fn (): array => $event === null ? [] : $this->publicSponsors($event),
            ),
        ]);
    }

    public function schedule(): Response
    {
        $event = $this->flagship();

        return Inertia::render('jubilee/schedule', [
            'event' => $event === null ? null : PublicEventResource::make($event),
            // The programme is not built yet. An empty schedule renders "to be
            // announced" rather than an empty page — the same promise the date
            // rule makes.
            'sessions' => [],
        ]);
    }

    public function sponsors(): Response
    {
        $event = $this->flagship();

        return Inertia::render('jubilee/sponsors', [
            'event' => $event === null ? null : PublicEventResource::make($event),
            'sponsors' => $event === null ? [] : $this->sponsorsByTier($event),
        ]);
    }

    public function faq(): Response
    {
        return Inertia::render('jubilee/faq', [
            'event' => ($event = $this->flagship()) === null
                ? null
                : PublicEventResource::make($event),
            'faqs' => $this->faqs(),
        ]);
    }

    /**
     * The flagship event, or null while none is flagged.
     *
     * Null is a real state: a fresh install with no seed data has no flagship,
     * and the microsite has to say so rather than 500.
     */
    private function flagship(): ?Event
    {
        return Event::query()
            ->published()
            ->where('is_flagship', true)
            ->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function faqs(): array
    {
        return Faq::query()
            ->where('group', FaqGroup::Jubilee)
            ->where('is_published', true)
            ->orderBy('display_order')
            ->get()
            ->map(fn (Faq $faq): array => [
                'question' => $faq->question,
                'answer' => $faq->answer,
            ])
            ->all();
    }

    /**
     * Public sponsors only. A sponsor who asked not to be listed is absent
     * from the payload, not hidden with CSS.
     *
     * @return array<int, array<string, mixed>>
     */
    private function publicSponsors(Event $event): array
    {
        return Sponsor::query()
            ->where('event_id', $event->id)
            ->where('is_public', true)
            ->where('status', SponsorStatus::Confirmed)
            ->with('package')
            ->orderBy('display_order')
            ->get()
            ->map(fn (Sponsor $sponsor): array => [
                'name' => $sponsor->name,
                'website' => $sponsor->website,
                'logo_url' => $sponsor->logo_path === null
                    ? null
                    : asset('storage/'.$sponsor->logo_path),
                'tier' => $sponsor->package?->tier->value,
                'tier_label' => $sponsor->package?->tier->label(),
            ])
            ->all();
    }

    /**
     * The sponsor wall, grouped by tier in display order.
     *
     * @return array<int, array{tier: string, label: string, sponsors: array<int, array<string, mixed>>}>
     */
    private function sponsorsByTier(Event $event): array
    {
        $groups = [];

        foreach ($this->publicSponsors($event) as $sponsor) {
            $tier = $sponsor['tier'] ?? 'custom';

            if (! isset($groups[$tier])) {
                $groups[$tier] = [
                    'tier' => $tier,
                    'label' => $sponsor['tier_label'] ?? '',
                    'sponsors' => [],
                ];
            }

            $groups[$tier]['sponsors'][] = $sponsor;
        }

        return array_values($groups);
    }
}
