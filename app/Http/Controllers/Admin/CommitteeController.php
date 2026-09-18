<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CommitteeMemberStatus;
use App\Enums\CommitteeType;
use App\Http\Controllers\Controller;
use App\Models\Committee;
use App\Models\CommitteeMember;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Committees and who sits on them.
 *
 * A committee member may be linked to an alumni record or entered as free
 * text: a head teacher, a guest of honour, a local dignitary. Office holders
 * also change, so a member row carries its own name and photo rather than
 * always reading through to `members` — the person who served in 2019 should
 * still show their 2019 designation after they leave.
 *
 * @see docs/05-modules.md section 10
 */
class CommitteeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Committee::class);

        $committees = Committee::query()
            ->with(['members' => fn ($query) => $query->orderBy('display_order')])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/committees/index', [
            'committees' => $committees
                ->map(fn (Committee $committee): array => $this->row($committee))
                ->all(),
            'options' => [
                'types' => CommitteeType::options(),
                'member_statuses' => CommitteeMemberStatus::options(),
            ],
            'can' => [
                'manage' => $request->user()?->can('committees.manage') ?? false,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Committee::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', 'in:'.implode(',', array_column(CommitteeType::cases(), 'value'))],
            'description' => ['nullable', 'string', 'max:2000'],
            'term_start' => ['nullable', 'date'],
            'term_end' => ['nullable', 'date', 'after_or_equal:term_start'],
        ]);

        Committee::query()->create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['name']),
            'status' => 'active',
        ]);

        return back()->with('success', __('common.states.saved'));
    }

    public function update(Request $request, Committee $committee): RedirectResponse
    {
        $this->authorize('update', $committee);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'type' => ['sometimes', 'string', 'in:'.implode(',', array_column(CommitteeType::cases(), 'value'))],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'term_start' => ['sometimes', 'nullable', 'date'],
            'term_end' => ['sometimes', 'nullable', 'date', 'after_or_equal:term_start'],
            'status' => ['sometimes', 'string', 'in:active,archived'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:999'],
        ]);

        $committee->update($validated);

        return back()->with('success', __('common.states.saved'));
    }

    public function addMember(Request $request, Committee $committee): RedirectResponse
    {
        $this->authorize('update', $committee);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'role' => ['required', 'string', 'max:120'],
            'designation' => ['nullable', 'string', 'max:120'],
            'member_ulid' => ['nullable', 'string', 'exists:members,ulid'],
            'contact_email' => ['nullable', 'email', 'max:191'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $member = isset($validated['member_ulid'])
            ? Member::query()->where('ulid', $validated['member_ulid'])->first()
            : null;

        CommitteeMember::query()->create([
            'committee_id' => $committee->id,
            'member_id' => $member?->id,
            'name' => $validated['name'],
            'role' => $validated['role'],
            'designation' => $validated['designation'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
            'start_date' => $committee->term_start,
            'status' => CommitteeMemberStatus::Active,
        ]);

        return back()->with('success', __('common.states.saved'));
    }

    /**
     * Stand someone down.
     *
     * The row stays, marked `past` with an end date — a committee's history is
     * part of the association's record, and deleting it would make the 2019
     * committee unreconstructable.
     */
    public function removeMember(Committee $committee, CommitteeMember $committeeMember): RedirectResponse
    {
        $this->authorize('update', $committee);

        abort_unless($committeeMember->committee_id === $committee->id, 404);

        $committeeMember->update([
            'status' => CommitteeMemberStatus::Past,
            'end_date' => now()->toDateString(),
        ]);

        return back()->with('success', __('admin.committees.member_removed'));
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Committee $committee): array
    {
        return [
            'id' => $committee->id,
            'slug' => $committee->slug,
            'name' => $committee->name,
            'type' => $committee->type->value,
            'type_label' => $committee->type->label(),
            'description' => $committee->description,
            'term_start' => $committee->term_start?->toDateString(),
            'term_end' => $committee->term_end?->toDateString(),
            'status' => $committee->status,
            'members' => $committee->members
                ->map(fn (CommitteeMember $member): array => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'role' => $member->role,
                    'designation' => $member->designation,
                    'photo_url' => $member->photo_path === null
                        ? null
                        : asset('storage/'.$member->photo_path),
                    'status' => $member->status->value,
                    'status_label' => $member->status->label(),
                    'member_ulid' => $member->member?->ulid,
                ])
                ->values()
                ->all(),
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'committee';
        $slug = $base;
        $suffix = 1;

        while (Committee::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$suffix;
        }

        return $slug;
    }
}
