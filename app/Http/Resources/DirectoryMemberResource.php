<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Member;
use App\Models\MemberPrivacy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The member as another approved member may see them.
 *
 * THE PRIVACY RULE, IN ONE PLACE.
 *
 * A field the member has hidden is ABSENT from the payload — `mergeWhen` omits
 * the key entirely, so the field name does not even appear in the JSON. It is
 * not blanked, not nulled on the client, not hidden with CSS. Nothing
 * downstream has to remember to filter it, and nothing downstream can forget.
 *
 * Address, emergency contact, student ID and date of birth are NEVER exposed
 * here regardless of flags — they exist for the association's administrative
 * use, not for member-to-member browsing.
 *
 * @see docs/08-security-privacy.md section 2
 *
 * @mixin Member
 */
class DirectoryMemberResource extends JsonResource
{
    /**
     * The int keys are the MergeValue placeholders that `mergeWhen` returns;
     * `resolve()` flattens them into string keys before anything sees them.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $privacy = $this->privacyOrDefaults();

        return [
            // Always visible to an approved member: this is what a directory
            // entry IS.
            'ulid' => $this->ulid,
            'full_name' => $this->full_name,
            'photo_url' => $this->photo_path === null
                ? null
                : asset('storage/'.$this->photo_path),
            'relation_type' => $this->relation_type->value,
            'relation_label' => $this->relation_type->label(),
            'membership_no' => $this->membership_no,
            'ssc_year' => $this->ssc_year,
            'batch' => $this->whenLoaded(
                'batch',
                fn (): ?string => $this->batch?->name,
            ),
            'bio' => $this->bio,

            $this->mergeWhen($privacy->show_workplace, fn (): array => [
                'occupation' => $this->occupation,
                'organization' => $this->organization,
                'job_title' => $this->job_title,
                'industry' => $this->industry,
            ]),

            $this->mergeWhen($privacy->show_location, fn (): array => [
                'city' => $this->city,
                'district' => $this->district,
                'division' => $this->division,
                'country' => $this->country,
            ]),

            $this->mergeWhen($privacy->show_phone, fn (): array => [
                'mobile' => $this->mobile,
                'whatsapp' => $this->whatsapp,
            ]),

            $this->mergeWhen($privacy->show_email, fn (): array => [
                'email' => $this->email,
            ]),

            'links' => MemberLinkResource::collection($this->whenLoaded('links')),
        ];
    }

    /**
     * Privacy settings, defaulting CLOSED when the row is somehow missing.
     *
     * MemberObserver creates the row for every member, so this should never
     * fire — but if it ever did, the safe failure is to disclose nothing
     * rather than everything.
     */
    private function privacyOrDefaults(): MemberPrivacy
    {
        return $this->privacy ?? new MemberPrivacy([
            'show_profile' => false,
            'show_phone' => false,
            'show_email' => false,
            'show_workplace' => false,
            'show_location' => false,
            'show_date_of_birth' => false,
            'show_in_batch_list' => false,
        ]);
    }
}
