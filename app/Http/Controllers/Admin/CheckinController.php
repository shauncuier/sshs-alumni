<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminEventResource;
use App\Http\Resources\RegistrationResource;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\Events\CheckinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The gate.
 *
 * Behind `events.checkin`, which a volunteer holds without holding
 * `events.edit` — they run the queue on the day without being able to change
 * the event, its prices or its schedule.
 *
 * Duplicate check-ins are prevented by a UNIQUE constraint in the database,
 * not by an application `if`. See CheckinService.
 *
 * @see docs/17-golden-jubilee.md section 5
 */
class CheckinController extends Controller
{
    public function __construct(
        private readonly CheckinService $checkins,
    ) {}

    /**
     * The scanner screen, with a name lookup.
     *
     * The lookup is not a nicety: on the day someone's phone will be dead,
     * their screenshot will be of the wrong event, or they will have forwarded
     * the pass to a relative. The gate has to be able to find them by name.
     */
    public function index(Request $request, Event $event): Response
    {
        $this->authorize('checkin', $event);

        $term = trim((string) $request->query('q', ''));

        return Inertia::render('admin/events/checkin', [
            'event' => AdminEventResource::make($event),
            'stats' => $this->checkins->stats($event),
            'recent' => $this->recent($event),
            'search' => ['q' => $term === '' ? null : $term],
            'matches' => $term === '' ? [] : $this->search($event, $term),
        ]);
    }

    /**
     * Registrations matching a name or phone number.
     *
     * Capped at ten: a gate operator scanning a list of fifty has lost more
     * time than they saved.
     *
     * @return array<int, array<string, mixed>>
     */
    private function search(Event $event, string $term): array
    {
        return EventRegistration::query()
            ->where('event_id', $event->id)
            ->where(function ($query) use ($term): void {
                $query->where('registrant_name', 'like', '%'.$term.'%')
                    ->orWhere('registrant_phone', 'like', '%'.$term.'%');
            })
            ->with(['ticketType', 'checkin.operator'])
            ->orderBy('registrant_name')
            ->limit(10)
            ->get()
            ->map(fn (EventRegistration $registration): array => [
                ...RegistrationResource::make($registration)->resolve(),
                'admissible' => $registration->status === RegistrationStatus::Confirmed,
                'already' => $registration->checkin !== null,
            ])
            ->all();
    }

    /**
     * A scan. The URL comes from a QR code, so it is a GET and carries the
     * pass's token — but it does NOT check anyone in. Scanning shows the
     * operator who is in front of them; admitting them is a separate, explicit
     * POST.
     *
     * A camera pointed at a wall of passes would otherwise admit all of them.
     */
    public function scan(Request $request, Event $event, string $ulid): Response
    {
        $this->authorize('checkin', $event);

        $registration = $this->checkins->resolve(
            $event,
            $ulid,
            $request->query('token') === null ? null : (string) $request->query('token'),
        );

        return Inertia::render('admin/events/checkin', [
            'event' => AdminEventResource::make($event),
            'stats' => $this->checkins->stats($event),
            'recent' => $this->recent($event),
            'scanned' => $registration === null
                ? ['found' => false]
                : [
                    'found' => true,
                    'registration' => RegistrationResource::make($registration)->resolve(),
                    'already' => $registration->checkin !== null,
                    'admissible' => $registration->status === RegistrationStatus::Confirmed,
                ],
        ]);
    }

    /**
     * Admit someone.
     */
    public function store(Request $request, Event $event, EventRegistration $registration): RedirectResponse
    {
        $this->authorize('checkin', $event);

        abort_unless($registration->event_id === $event->id, 404);

        $user = $request->user();
        abort_if($user === null, 403);

        $validated = $request->validate([
            'gate' => ['nullable', 'string', 'max:60'],
        ]);

        $result = $this->checkins->checkIn(
            registration: $registration,
            operator: $user,
            gate: $validated['gate'] ?? null,
            device: substr((string) $request->userAgent(), 0, 120),
        );

        return match ($result['status']) {
            'checked_in' => back()->with('success', __('admin.checkin.admitted', [
                'name' => $registration->registrant_name,
            ])),

            // Not an error. It is a normal thing to happen at a gate, and the
            // operator needs the time and the operator's name, not a red page.
            'already' => back()->with('warning', __('admin.checkin.already', [
                'name' => $registration->registrant_name,
                'time' => $result['checkin']?->checked_in_at->format('H:i') ?? '',
                'operator' => $result['checkin']?->operator->name ?? '',
            ])),

            default => back()->with('error', __(
                'admin.checkin.refused_'.($result['reason'] ?? 'unknown'),
            )),
        };
    }

    /**
     * The last few admissions, so the operator can see the queue moving and
     * spot their own mistake immediately.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recent(Event $event): array
    {
        return $event->checkins()
            ->with(['registration', 'operator'])
            ->latest('checked_in_at')
            ->limit(10)
            ->get()
            ->map(fn ($checkin): array => [
                'name' => $checkin->registration?->registrant_name,
                'checked_in_at' => $checkin->checked_in_at->toIso8601String(),
                'gate' => $checkin->gate,
                'operator' => $checkin->operator?->name,
            ])
            ->all();
    }
}
