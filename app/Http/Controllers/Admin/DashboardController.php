<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MemberStatus;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\CrmContact;
use App\Models\CrmTask;
use App\Models\Event;
use App\Models\Member;
use Illuminate\Http\Request;
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
    public function __invoke(Request $request): Response
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

            // The CRM's own numbers, for whoever can actually open it.
            // Deferred because they are three more queries and the member
            // counts above are what the page is mostly for.
            'crm' => Inertia::defer(fn (): ?array => $this->crmSummary($request)),
        ]);
    }

    /**
     * What is outstanding in the CRM, for the dashboard.
     *
     * Null for anybody without `crm.view` — a widget that renders zeroes at
     * someone who cannot open the section is just clutter.
     *
     * @return array{contacts: int, unassigned: int, my_open_tasks: int, overdue_tasks: int}|null
     */
    private function crmSummary(Request $request): ?array
    {
        $user = $request->user();

        if ($user === null || ! $user->can('crm.view')) {
            return null;
        }

        $open = CrmTask::query()->whereNotIn('status', [TaskStatus::Done, TaskStatus::Cancelled]);

        return [
            'contacts' => CrmContact::query()->count(),
            // An unowned contact is nobody's job, which is how prospects go
            // cold. Surfacing the count is the cheapest fix for that.
            'unassigned' => CrmContact::query()->whereNull('owner_id')->count(),
            'my_open_tasks' => (clone $open)->where('assigned_to', $user->id)->count(),
            'overdue_tasks' => (clone $open)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->count(),
        ];
    }
}
