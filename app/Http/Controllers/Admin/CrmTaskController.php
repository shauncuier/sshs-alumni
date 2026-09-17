<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CrmTaskResource;
use App\Models\CrmContact;
use App\Models\CrmTask;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Follow-ups.
 *
 * A task may hang off a contact, off a member, or off nothing at all — "book
 * the hall" belongs to no particular person.
 *
 * Overdue is computed server-side in the resource, so a device with a wrong
 * clock cannot hide a task that is late.
 *
 * @see docs/05-modules.md section 6
 */
class CrmTaskController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CrmTask::class);

        $user = $request->user();

        $filters = [
            'status' => (string) $request->query('status', TaskStatus::Open->value),
            'assignee' => (string) $request->query('assignee', ''),
            'overdue' => $request->boolean('overdue'),
        ];

        $tasks = CrmTask::query()
            ->with(['assignee', 'creator', 'subject'])
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['assignee'] === 'me', fn ($query) => $query->where('assigned_to', $user?->id))
            ->when($filters['assignee'] === 'none', fn ($query) => $query->whereNull('assigned_to'))
            ->when($filters['overdue'], fn ($query) => $query
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->whereNotIn('status', [TaskStatus::Done, TaskStatus::Cancelled]))
            // Undated tasks last: a task with no date is a wish, and a list
            // that puts wishes above today's calls is useless.
            ->orderByRaw('case when due_at is null then 1 else 0 end')
            ->orderBy('due_at')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('admin/crm/tasks', [
            'tasks' => CrmTaskResource::collection($tasks),
            'filters' => [
                'status' => $filters['status'] === '' ? null : $filters['status'],
                'assignee' => $filters['assignee'] === '' ? null : $filters['assignee'],
                'overdue' => $filters['overdue'],
            ],
            'options' => [
                'statuses' => TaskStatus::options(),
                'priorities' => TaskPriority::options(),
                'assignees' => $this->assignees(),
            ],
            'can' => [
                'create' => $user?->can('create', CrmTask::class) ?? false,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CrmTask::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['nullable', 'date'],
            'priority' => ['required', 'string', 'in:'.implode(',', array_column(TaskPriority::cases(), 'value'))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            // Optional: a task may stand alone.
            'contact_ulid' => ['nullable', 'string', 'exists:crm_contacts,ulid'],
        ]);

        $subject = isset($validated['contact_ulid'])
            ? CrmContact::query()->where('ulid', $validated['contact_ulid'])->first()
            : null;

        CrmTask::query()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
            'priority' => $validated['priority'],
            'status' => TaskStatus::Open,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'created_by' => $request->user()?->id,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->id,
        ]);

        return back()->with('success', __('common.states.saved'));
    }

    public function update(Request $request, CrmTask $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:'.implode(',', array_column(TaskStatus::cases(), 'value'))],
            'priority' => ['sometimes', 'string', 'in:'.implode(',', array_column(TaskPriority::cases(), 'value'))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (isset($validated['status'])) {
            $status = TaskStatus::from($validated['status']);

            // Completion time is the server's, not a field. Reopening clears
            // it, so a reopened task does not claim to be finished.
            $validated['completed_at'] = $status === TaskStatus::Done ? now() : null;
        }

        $task->update($validated);

        return back()->with('success', __('common.states.saved'));
    }

    public function destroy(CrmTask $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return back()->with('success', __('admin.crm.task_deleted'));
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function assignees(): array
    {
        return User::query()
            ->permission('crm.view')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => ['value' => (string) $user->id, 'label' => $user->name])
            ->all();
    }
}
