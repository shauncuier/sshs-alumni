<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MemberStatus;
use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Event;
use App\Models\Member;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The admin overview.
     *
     * Counts are computed in the database rather than by loading collections —
     * at fifty thousand members, `Member::all()->count()` is a memory problem
     * and a COUNT(*) is not.
     */
    public function __invoke(): Response
    {
        return Inertia::render('admin/dashboard', [
            'stats' => [
                'total_members' => Member::query()->count(),
                'verified_members' => Member::query()->approved()->count(),
                'pending_registrations' => Member::query()
                    ->whereIn('status', [MemberStatus::Pending, MemberStatus::UnderReview])
                    ->count(),
                'new_this_month' => Member::query()
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->count(),
                'batches' => Batch::query()->count(),
                'events' => Event::query()->published()->count(),
            ],

            // The flagship event, so the dashboard can say plainly whether the
            // Jubilee date has been published yet.
            'jubilee' => Inertia::defer(function (): ?array {
                $event = Event::query()->where('is_flagship', true)->first();

                if ($event === null) {
                    return null;
                }

                return [
                    'ulid' => $event->ulid,
                    'title' => $event->title,
                    'date_is_tba' => $event->dateIsTba(),
                    'starts_at' => $event->starts_at?->toIso8601String(),
                ];
            }),
        ]);
    }
}
