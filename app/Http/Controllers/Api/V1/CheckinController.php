<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\RegistrationResource;
use App\Models\Event;
use App\Services\Events\CheckinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile scanner endpoint for event check-in volunteers.
 *
 * @see docs/10-api.md section 3 & 7
 * @see docs/17-golden-jubilee.md section 5
 */
class CheckinController extends Controller
{
    public function __construct(
        private readonly CheckinService $checkins,
    ) {}

    /**
     * Inspect a pass from a scan before admitting.
     */
    public function scan(Request $request, Event $event, string $ulid): JsonResponse
    {
        $this->authorize('checkin', $event);

        $token = $request->query('token') === null ? null : (string) $request->query('token');

        $registration = $this->checkins->resolve($event, $ulid, $token);

        if ($registration === null) {
            return response()->json([
                'found' => false,
                'message' => 'Pass not found or invalid for this event.',
            ], 404);
        }

        return response()->json([
            'found' => true,
            'registration' => RegistrationResource::make($registration)->resolve(),
            'already' => $registration->checkin !== null,
            'admissible' => $registration->status === RegistrationStatus::Confirmed,
            'checkin' => $registration->checkin === null ? null : [
                'checked_in_at' => $registration->checkin->checked_in_at->toIso8601String(),
                'operator' => $registration->checkin->operator?->name,
                'gate' => $registration->checkin->gate,
            ],
        ]);
    }

    /**
     * Check a scanned pass in.
     */
    public function store(Request $request, Event $event): JsonResponse
    {
        $this->authorize('checkin', $event);

        $validated = $request->validate([
            'ulid' => ['required', 'string'],
            'token' => ['nullable', 'string'],
            'gate' => ['nullable', 'string', 'max:60'],
        ]);

        $registration = $this->checkins->resolve(
            $event,
            $validated['ulid'],
            $validated['token'] ?? null,
        );

        if ($registration === null) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Pass not found or invalid for this event.',
            ], 404);
        }

        $user = $request->user();
        abort_if($user === null, 401);

        $result = $this->checkins->checkIn(
            registration: $registration,
            operator: $user,
            gate: $validated['gate'] ?? null,
            device: substr((string) $request->userAgent(), 0, 120),
        );

        return match ($result['status']) {
            'checked_in' => response()->json([
                'status' => 'checked_in',
                'message' => __('admin.checkin.admitted', ['name' => $registration->registrant_name]),
                'registration' => RegistrationResource::make($registration)->resolve(),
                'checked_in_at' => $result['checkin']?->checked_in_at->toIso8601String(),
            ], 200),

            'already' => response()->json([
                'status' => 'already_checked_in',
                'message' => __('admin.checkin.already', [
                    'name' => $registration->registrant_name,
                    'time' => $result['checkin']?->checked_in_at->format('H:i') ?? '',
                    'operator' => $result['checkin']?->operator->name ?? '',
                ]),
                'checked_in_at' => $result['checkin']?->checked_in_at->toIso8601String(),
                'operator' => $result['checkin']?->operator->name ?? null,
                'gate' => $result['checkin']?->gate,
                'registration' => RegistrationResource::make($registration)->resolve(),
            ], 422),

            default => response()->json([
                'status' => 'not_admissible',
                'message' => __('admin.checkin.refused_'.($result['reason'] ?? 'unknown')),
                'reason' => $result['reason'],
            ], 422),
        };
    }

    /**
     * Real-time gate statistics for the scanner screen.
     */
    public function stats(Request $request, Event $event): JsonResponse
    {
        $this->authorize('checkin', $event);

        return response()->json([
            'stats' => $this->checkins->stats($event),
        ]);
    }
}
