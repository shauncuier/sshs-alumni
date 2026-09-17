<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CrmContactType;
use App\Enums\PipelineStage;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CrmContactRequest;
use App\Http\Resources\CrmActivityResource;
use App\Http\Resources\CrmContactResource;
use App\Http\Resources\CrmTaskResource;
use App\Models\CrmContact;
use App\Models\CrmTag;
use App\Models\Member;
use App\Models\User;
use App\Services\Crm\ActivityLogger;
use App\Services\Crm\PipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRM contacts.
 *
 * A contact is anyone the association deals with who is not (yet) an alumni
 * record: prospects, donors, sponsors, guests, partners. When a contact turns
 * out to be an alumnus, `member_id` links them and the two histories become
 * one feed — the person is never duplicated.
 *
 * @see docs/05-modules.md section 6
 */
class CrmContactController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activities,
        private readonly PipelineService $pipeline,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CrmContact::class);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'type' => (string) $request->query('type', ''),
            'stage' => (string) $request->query('stage', ''),
            'owner' => (string) $request->query('owner', ''),
            'tag' => (string) $request->query('tag', ''),
        ];

        $contacts = CrmContact::query()
            ->with(['owner', 'member', 'tags'])
            ->withCount(['tasks' => fn ($query) => $query->where('status', TaskStatus::Open)])
            ->when($filters['q'] !== '', fn ($query) => $query->where(function ($inner) use ($filters): void {
                $needle = '%'.$filters['q'].'%';

                $inner->where('name', 'like', $needle)
                    ->orWhere('organization_name', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('phone', 'like', $needle);
            }))
            ->when($filters['type'] !== '', fn ($query) => $query->where('type', $filters['type']))
            ->when($filters['stage'] !== '', fn ($query) => $query->where('pipeline_status', $filters['stage']))
            ->when($filters['owner'] === 'me', fn ($query) => $query->where('owner_id', $request->user()?->id))
            ->when($filters['owner'] === 'none', fn ($query) => $query->whereNull('owner_id'))
            ->when(
                $filters['tag'] !== '',
                fn ($query) => $query->whereHas('tags', fn ($inner) => $inner->where('crm_tags.id', $filters['tag'])),
            )
            // Least recently touched first would be the useful default for
            // chasing cold leads, but a list that reorders under you as you
            // work it is worse. Newest activity first, and the "no owner"
            // filter is how cold leads are found.
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/crm/contacts/index', [
            'contacts' => CrmContactResource::collection($contacts),
            'filters' => array_map(fn (string $value): ?string => $value === '' ? null : $value, $filters),
            'options' => $this->options(),
            'counts' => Inertia::defer(fn (): array => $this->pipeline->counts()),
            'can' => [
                'create' => $request->user()?->can('create', CrmContact::class) ?? false,
            ],
        ]);
    }

    public function show(Request $request, CrmContact $contact): Response
    {
        $this->authorize('view', $contact);

        $contact->load(['owner', 'member', 'tags']);

        $user = $request->user();

        return Inertia::render('admin/crm/contacts/show', [
            'contact' => CrmContactResource::make($contact),

            // ONE feed across both of this person's records. A contact that
            // has been linked to a member shows the calls that preceded the
            // registration and the registration itself, in order.
            'timeline' => Inertia::defer(fn (): array => CrmActivityResource::collection(
                $this->activities->timelineFor($contact),
            )->resolve()),

            'tasks' => Inertia::defer(fn (): array => CrmTaskResource::collection(
                $contact->tasks()->with(['assignee', 'creator'])->orderBy('due_at')->get(),
            )->resolve()),

            'options' => $this->options(),
            'can' => [
                'update' => $user?->can('update', $contact) ?? false,
                'assign' => $user?->can('assign', $contact) ?? false,
                'delete' => $user?->can('delete', $contact) ?? false,
            ],
        ]);
    }

    public function store(CrmContactRequest $request): RedirectResponse
    {
        $this->authorize('create', CrmContact::class);

        $contact = CrmContact::query()->create([
            ...$request->validated(),
            // A new contact belongs to whoever entered it until somebody says
            // otherwise. An unowned contact is nobody's job.
            'owner_id' => $request->validated('owner_id') ?? $request->user()?->id,
        ]);

        $this->activities->system(
            subject: $contact,
            subjectLine: __('admin.crm.system.created'),
        );

        return to_route('admin.crm.contacts.show', $contact)
            ->with('success', __('common.states.saved'));
    }

    public function update(CrmContactRequest $request, CrmContact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);

        $validated = $request->validated();

        // The stage and the owner move through PipelineService, which records
        // them on the timeline. Letting them through here would lose that.
        unset($validated['pipeline_status'], $validated['owner_id']);

        $contact->update($validated);

        return back()->with('success', __('common.states.saved'));
    }

    public function destroy(CrmContact $contact): RedirectResponse
    {
        $this->authorize('delete', $contact);

        // Soft delete: the activities stay, and so does the record of who the
        // association was talking to.
        $contact->delete();

        return to_route('admin.crm.contacts.index')
            ->with('success', __('admin.crm.deleted'));
    }

    /**
     * Move a contact along the pipeline. Also the drop target for the board.
     */
    public function move(Request $request, CrmContact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:'.implode(',', array_column(PipelineStage::cases(), 'value'))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->pipeline->moveTo(
            contact: $contact,
            stage: PipelineStage::from($validated['stage']),
            actor: $request->user(),
            note: $validated['note'] ?? null,
        );

        return back();
    }

    public function assign(Request $request, CrmContact $contact): RedirectResponse
    {
        $this->authorize('assign', $contact);

        $validated = $request->validate([
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $this->pipeline->assign(
            contact: $contact,
            owner: isset($validated['owner_id'])
                ? User::query()->whereKey($validated['owner_id'])->first()
                : null,
            actor: $request->user(),
        );

        return back()->with('success', __('common.states.saved'));
    }

    /**
     * Link this contact to the alumni record that is the same person.
     */
    public function link(Request $request, CrmContact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'member_ulid' => ['required', 'string', 'exists:members,ulid'],
        ]);

        $member = Member::query()->where('ulid', $validated['member_ulid'])->firstOrFail();

        // One alumni record, one contact. Two contacts pointing at the same
        // member would split the very history this is meant to join.
        $taken = CrmContact::query()
            ->where('member_id', $member->id)
            ->whereKeyNot($contact->id)
            ->exists();

        if ($taken) {
            return back()->with('error', __('admin.crm.member_already_linked'));
        }

        $this->pipeline->linkToMember($contact, $member, $request->user());

        return back()->with('success', __('admin.crm.linked'));
    }

    public function unlink(Request $request, CrmContact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);

        $this->pipeline->unlinkMember($contact, $request->user());

        return back()->with('success', __('admin.crm.unlinked'));
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'types' => CrmContactType::options(),
            'stages' => PipelineStage::options(),
            'tags' => CrmTag::query()
                ->orderBy('name')
                ->get(['id', 'name', 'color'])
                ->all(),
            // Only people who can actually work a contact are offered as
            // owners — assigning one to someone who cannot open the CRM is a
            // silent dead end.
            'owners' => User::query()
                ->permission('crm.view')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $user): array => ['value' => (string) $user->id, 'label' => $user->name])
                ->all(),
        ];
    }
}
