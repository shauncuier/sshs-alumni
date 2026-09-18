<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AssignmentStatus;
use App\Enums\VolunteerStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Member;
use App\Models\Volunteer;
use App\Models\VolunteerAssignment;
use App\Models\VolunteerTeam;
use App\Services\Crm\ActivityLogger;
use App\Support\Paginated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Volunteers, teams and assignments.
 *
 * A volunteer may or may not be a member: a parent, a former teacher, a
 * sibling who turns up to help. `member_id` and `crm_contact_id` are both
 * optional for exactly that reason, and a name alone is enough to record
 * somebody who offered.
 *
 * @see docs/05-modules.md section 9
 */
class VolunteerController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activities,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Volunteer::class);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'team' => (string) $request->query('team', ''),
        ];

        $volunteers = Volunteer::query()
            ->with(['member', 'assignments.team', 'assignments.event'])
            ->when($filters['q'] !== '', fn ($query) => $query->where(function ($inner) use ($filters): void {
                $needle = '%'.$filters['q'].'%';
                $inner->where('name', 'like', $needle)
                    ->orWhere('phone', 'like', $needle)
                    ->orWhere('email', 'like', $needle);
            }))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['team'] !== '', fn ($query) => $query->whereHas(
                'assignments',
                fn ($inner) => $inner->where('volunteer_team_id', $filters['team']),
            ))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('admin/volunteers/index', [
            'volunteers' => Paginated::from($volunteers, fn (Volunteer $volunteer): array => $this->row($volunteer)),
            'filters' => array_map(fn (string $v): ?string => $v === '' ? null : $v, $filters),
            'teams' => VolunteerTeam::query()
                ->withCount(['assignments' => fn ($query) => $query->whereNot('status', AssignmentStatus::Cancelled)])
                ->orderBy('display_order')
                ->get()
                ->map(fn (VolunteerTeam $team): array => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'description' => $team->description,
                    'is_active' => $team->is_active,
                    'assignments_count' => $team->assignments_count,
                    'lead' => $team->lead?->full_name,
                ])
                ->all(),
            'options' => [
                'statuses' => VolunteerStatus::options(),
                'assignment_statuses' => AssignmentStatus::options(),
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
                'manage' => $request->user()?->can('volunteers.manage') ?? false,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Volunteer::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:191'],
            'member_ulid' => ['nullable', 'string', 'exists:members,ulid'],
            'availability' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $member = isset($validated['member_ulid'])
            ? Member::query()->where('ulid', $validated['member_ulid'])->first()
            : null;

        Volunteer::query()->create([
            'member_id' => $member?->id,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'availability' => $validated['availability'] ?? null,
            'location' => $validated['location'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => VolunteerStatus::Applied,
            'applied_at' => now(),
        ]);

        return back()->with('success', __('common.states.saved'));
    }

    public function update(Request $request, Volunteer $volunteer): RedirectResponse
    {
        $this->authorize('update', $volunteer);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_column(VolunteerStatus::cases(), 'value'))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $volunteer->update($validated);

        return back()->with('success', __('common.states.saved'));
    }

    /**
     * Put a volunteer on a team, and on the day.
     *
     * Recorded on their CRM timeline when they have one — the specification's
     * example flow ends with "Assigned to Reception team" for a reason: it is
     * the last thing that happens to somebody before the event, and the one
     * most likely to be asked about afterwards.
     */
    public function assign(Request $request, Volunteer $volunteer): RedirectResponse
    {
        $this->authorize('update', $volunteer);

        $validated = $request->validate([
            'volunteer_team_id' => ['nullable', 'integer', 'exists:volunteer_teams,id'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'responsibility' => ['nullable', 'string', 'max:200'],
            'shift_start' => ['nullable', 'date'],
            'shift_end' => ['nullable', 'date', 'after_or_equal:shift_start'],
        ]);

        $assignment = VolunteerAssignment::query()->create([
            ...$validated,
            'volunteer_id' => $volunteer->id,
            'status' => AssignmentStatus::Assigned,
            'assigned_by' => $request->user()?->id,
        ]);

        $subject = $volunteer->member ?? $volunteer->crmContact;

        if ($subject !== null) {
            $team = $assignment->team;

            $this->activities->system(
                subject: $subject,
                subjectLine: $team === null
                    ? __('admin.volunteers.system.assigned')
                    : __('admin.volunteers.system.assigned_team', ['team' => $team->name]),
                meta: ['assignment_id' => $assignment->id],
            );
        }

        return back()->with('success', __('admin.volunteers.assigned'));
    }

    public function updateAssignment(Request $request, VolunteerAssignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment->volunteer);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_column(AssignmentStatus::cases(), 'value'))],
        ]);

        $assignment->update($validated);

        return back()->with('success', __('common.states.saved'));
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Volunteer $volunteer): array
    {
        return [
            'id' => $volunteer->id,
            'name' => $volunteer->name,
            'phone' => $volunteer->phone,
            'email' => $volunteer->email,
            'availability' => $volunteer->availability,
            'location' => $volunteer->location,
            'status' => $volunteer->status->value,
            'status_label' => $volunteer->status->label(),
            'notes' => $volunteer->notes,
            'member_ulid' => $volunteer->member?->ulid,
            'assignments' => $volunteer->assignments
                ->map(fn (VolunteerAssignment $assignment): array => [
                    'id' => $assignment->id,
                    'team' => $assignment->team?->name,
                    'event' => $assignment->event?->title,
                    'responsibility' => $assignment->responsibility,
                    'status' => $assignment->status->value,
                    'status_label' => $assignment->status->label(),
                ])
                ->values()
                ->all(),
        ];
    }
}
