<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrmContact;
use App\Models\CrmTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tags, applying to members and contacts alike through one polymorphic pivot.
 *
 * Tags are shared vocabulary rather than personal bookmarks, so creating one
 * needs `crm.manage` — a CRM where everybody invents their own labels stops
 * being searchable within a month.
 *
 * @see docs/05-modules.md section 6
 */
class CrmTagController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CrmContact::class);

        $tags = CrmTag::query()
            ->withCount(['contacts', 'members'])
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/crm/tags', [
            'tags' => $tags->map(fn (CrmTag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
                'description' => $tag->description,
                'contacts_count' => $tag->contacts_count,
                'members_count' => $tag->members_count,
            ])->all(),
            'can' => [
                'manage' => $request->user()?->can('crm.manage') ?? false,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CrmContact::class);

        CrmTag::query()->create($this->validated($request));

        return back()->with('success', __('common.states.saved'));
    }

    public function update(Request $request, CrmTag $tag): RedirectResponse
    {
        $this->authorize('create', CrmContact::class);

        $tag->update($this->validated($request, $tag));

        return back()->with('success', __('common.states.saved'));
    }

    /**
     * Deleting a tag detaches it everywhere — the pivot cascades. That is the
     * intent: a retired label should stop appearing, not linger as an id.
     */
    public function destroy(CrmTag $tag): RedirectResponse
    {
        $this->authorize('create', CrmContact::class);

        $tag->delete();

        return back()->with('success', __('admin.crm.tag_deleted'));
    }

    /**
     * Attach or detach a tag on one contact.
     *
     * `toggle` rather than separate endpoints: the UI is a row of chips, and
     * the meaningful action is "this contact is / is not this".
     */
    public function toggle(Request $request, CrmContact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'tag_id' => ['required', 'integer', 'exists:crm_tags,id'],
        ]);

        $contact->tags()->toggle([$validated['tag_id']]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?CrmTag $tag = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('crm_tags', 'name')->ignore($tag?->id),
            ],
            // A hex colour or nothing. Free text here ends up in a style
            // attribute.
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
