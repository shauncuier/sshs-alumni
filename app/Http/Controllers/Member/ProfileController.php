<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MediaCollection;
use App\Enums\MemberLinkType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\PrivacyUpdateRequest;
use App\Http\Requests\Member\ProfilePhotoRequest;
use App\Http\Requests\Member\ProfileUpdateRequest;
use App\Http\Resources\AdminMemberResource;
use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberLink;
use App\Services\Media\MediaService;
use App\Services\Membership\ProfileCompletionCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A member editing their own record.
 *
 * AdminMemberResource is reused here because a member may see their own full
 * record — including the fields the directory hides from others. What they may
 * CHANGE is narrowed by the form request, not by the resource: status,
 * membership number and verification are absent from its rules, so they cannot
 * be written however the payload is shaped.
 */
class ProfileController extends Controller
{
    public function edit(Request $request, ProfileCompletionCalculator $completion): Response
    {
        $member = $this->memberFor($request);

        $member->load(['batch', 'privacy', 'links']);

        return Inertia::render('member/profile', [
            'member' => AdminMemberResource::make($member),
            'completion' => [
                'percent' => $member->profile_completion,
                'missing' => $completion->missingGroups($member),
            ],
            'options' => [
                // The upload limit comes from the same config the validator
                // reads, so the help text cannot promise a size the server
                // then rejects.
                'photo_max_kb' => (int) config(
                    'media.collections.'.MediaCollection::Profile->value.'.max_kb',
                    2048,
                ),
                'genders' => Gender::options(),
                'blood_groups' => BloodGroup::options(),
                'link_types' => MemberLinkType::options(),
                'batches' => Batch::query()
                    ->orderByDesc('ssc_year')
                    ->get(['id', 'name'])
                    ->map(fn (Batch $batch): array => [
                        'value' => (string) $batch->id,
                        'label' => $batch->name,
                    ]),
            ],
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $member = $this->memberFor($request);

        $this->authorize('update', $member);

        $validated = $request->validated();
        $links = $validated['links'] ?? null;
        unset($validated['links']);

        $member->update($validated);

        if (is_array($links)) {
            $this->syncLinks($member, $links);
        }

        return back()->with('success', __('common.states.saved'));
    }

    /**
     * Privacy is a separate endpoint from the rest of the profile.
     *
     * A member changing what the world can see should not have to resubmit
     * their whole profile to do it, and the two concerns validate differently.
     */
    public function updatePrivacy(PrivacyUpdateRequest $request): RedirectResponse
    {
        $member = $this->memberFor($request);

        $this->authorize('update', $member);

        $member->privacy()->update($request->validated());

        return back()->with('success', __('common.states.saved'));
    }

    public function updatePhoto(ProfilePhotoRequest $request, MediaService $media): RedirectResponse
    {
        $member = $this->memberFor($request);

        $this->authorize('update', $member);

        $stored = $media->store(
            $request->file('photo'),
            MediaCollection::Profile,
            $member,
        );

        $member->update(['photo_path' => $stored->path]);

        return back()->with('success', __('common.states.saved'));
    }

    /**
     * Replace the member's links wholesale.
     *
     * @param  array<int, array{type: string, url: string}>  $links
     */
    private function syncLinks(Member $member, array $links): void
    {
        $member->links()->delete();

        foreach ($links as $order => $link) {
            // The shape is guaranteed by ProfileUpdateRequest.
            if (blank($link['url'])) {
                continue;
            }

            MemberLink::query()->create([
                'member_id' => $member->id,
                'type' => $link['type'],
                'url' => $link['url'],
                'display_order' => $order,
            ]);
        }
    }

    private function memberFor(Request $request): Member
    {
        $member = $request->user()?->member;

        // A signed-in user with no alumni record has nothing to edit here —
        // office staff belong in the admin panel.
        abort_if($member === null, 404);

        return $member;
    }
}
