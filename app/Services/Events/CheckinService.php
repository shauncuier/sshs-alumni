<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventCheckin;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Database\QueryException;

/**
 * The gate.
 *
 * Duplicate check-ins are prevented by the UNIQUE constraint on
 * `event_checkins.event_registration_id`, not by an application `if`. Two
 * volunteers scanning the same pass at two gates in the same second is exactly
 * the case a read-then-write check loses, and it is the case that causes an
 * argument in front of a queue.
 *
 * So the insert is attempted, and a constraint violation is read as "already
 * checked in" — which is the truth, whoever won the race.
 *
 * @see docs/17-golden-jubilee.md section 5
 */
class CheckinService
{
    /**
     * Find a registration from a scanned QR payload.
     *
     * The payload is a ULID, and the registration is read from the DATABASE by
     * it — never trusted from the code itself. A forged QR can therefore only
     * point at a real registration or at nothing.
     *
     * The `qr_token` is checked as well, so a ULID harvested from a URL
     * elsewhere on the site cannot be turned into a pass.
     */
    public function resolve(Event $event, string $ulid, ?string $token = null): ?EventRegistration
    {
        $registration = EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('ulid', $ulid)
            ->with(['member', 'ticketType', 'guests', 'checkin.operator'])
            ->first();

        if ($registration === null) {
            return null;
        }

        if ($token !== null && ! hash_equals($registration->qr_token, $token)) {
            return null;
        }

        return $registration;
    }

    /**
     * Check a registration in.
     *
     * Returns the outcome rather than throwing, because "already checked in"
     * is a normal thing to happen at a gate and the operator needs to be told
     * when and by whom — not shown an error page.
     *
     * @return array{status: 'checked_in'|'already'|'not_admissible', checkin: EventCheckin|null, reason: string|null}
     */
    public function checkIn(
        EventRegistration $registration,
        User $operator,
        ?string $gate = null,
        ?string $device = null,
    ): array {
        if ($registration->status === RegistrationStatus::Cancelled) {
            return [
                'status' => 'not_admissible',
                'checkin' => null,
                'reason' => 'cancelled',
            ];
        }

        // A waitlisted registration is not a ticket. The committee promotes it
        // first; the gate does not make that decision.
        if ($registration->status === RegistrationStatus::Waitlisted) {
            return [
                'status' => 'not_admissible',
                'checkin' => null,
                'reason' => 'waitlisted',
            ];
        }

        try {
            $checkin = EventCheckin::query()->create([
                'event_registration_id' => $registration->id,
                'event_id' => $registration->event_id,
                'checked_in_at' => now(),
                'operator_id' => $operator->id,
                'gate' => $gate,
                'device' => $device,
            ]);
        } catch (QueryException $exception) {
            // The unique constraint fired: someone else got there first, or
            // this pass has already been through the gate.
            if (! $this->isUniqueViolation($exception)) {
                throw $exception;
            }

            return [
                'status' => 'already',
                'checkin' => $registration->checkin()->with('operator')->first(),
                'reason' => null,
            ];
        }

        return [
            'status' => 'checked_in',
            'checkin' => $checkin->load('operator'),
            'reason' => null,
        ];
    }

    /**
     * How the gate is doing, for the operator's own screen.
     *
     * @return array{expected: int, checked_in: int, remaining: int}
     */
    public function stats(Event $event): array
    {
        $expected = (int) $event->registrations()
            ->where('status', RegistrationStatus::Confirmed)
            ->count();

        $checkedIn = (int) $event->checkins()->count();

        return [
            'expected' => $expected,
            'checked_in' => $checkedIn,
            'remaining' => max(0, $expected - $checkedIn),
        ];
    }

    /**
     * MySQL reports 1062 and SQLite reports 19 with UNIQUE in the message, so
     * both drivers have to be recognised — the suite runs on SQLite and
     * production runs on MySQL.
     */
    private function isUniqueViolation(QueryException $exception): bool
    {
        $code = $exception->errorInfo[1] ?? null;

        if ($code === 1062) {
            return true;
        }

        return $code === 19
            && str_contains(strtoupper($exception->getMessage()), 'UNIQUE');
    }
}
