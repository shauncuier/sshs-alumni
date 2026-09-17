<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The member as an administrator holding `members.view` may see them.
 *
 * The full record, because verifying an application means reading what the
 * applicant submitted. Privacy flags do NOT apply here — they govern what
 * other members see, not whether the committee can do its job — but they are
 * included so an administrator can see what the member chose.
 *
 * Reaching this resource at all requires the permission; a Batch Coordinator
 * is additionally narrowed to their own batch by MemberPolicy.
 *
 * @see docs/08-security-privacy.md section 2
 *
 * @mixin Member
 */
class AdminMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'membership_no' => $this->membership_no,

            'full_name' => $this->full_name,
            'full_name_bn' => $this->full_name_bn,
            'photo_url' => $this->photo_path === null
                ? null
                : asset('storage/'.$this->photo_path),
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender?->value,
            'gender_label' => $this->gender?->label(),
            'blood_group' => $this->blood_group?->value,

            'relation_type' => $this->relation_type->value,
            'relation_label' => $this->relation_type->label(),

            'batch' => $this->whenLoaded(
                'batch',
                fn (): ?string => $this->batch?->getTranslation('name'),
            ),
            'batch_id' => $this->batch_id,
            'ssc_year' => $this->ssc_year,
            'student_id' => $this->student_id,
            'admission_year' => $this->admission_year,
            'group_stream' => $this->group_stream,
            'section' => $this->section,
            'house' => $this->house,
            'higher_education' => $this->higher_education,

            'occupation' => $this->occupation,
            'organization' => $this->organization,
            'job_title' => $this->job_title,
            'industry' => $this->industry,
            'business_info' => $this->business_info,

            'country' => $this->country,
            'division' => $this->division,
            'district' => $this->district,
            'city' => $this->city,
            'address' => $this->address,

            'mobile' => $this->mobile,
            'whatsapp' => $this->whatsapp,
            'email' => $this->email,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,

            'bio' => $this->bio,
            'bio_bn' => $this->bio_bn,
            'skills' => $this->skills,
            'interests' => $this->interests,

            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'verified_by' => $this->whenLoaded(
                'verifier',
                fn (): ?string => $this->verifier?->name,
            ),
            'registered_at' => $this->registered_at?->toIso8601String(),
            'profile_completion' => $this->profile_completion,

            // Shown so an administrator can see what the member chose, and
            // respect it when, for example, drafting a public story.
            'privacy' => $this->whenLoaded('privacy', fn (): array => [
                'show_profile' => $this->privacy->show_profile,
                'show_phone' => $this->privacy->show_phone,
                'show_email' => $this->privacy->show_email,
                'show_workplace' => $this->privacy->show_workplace,
                'show_location' => $this->privacy->show_location,
                'show_date_of_birth' => $this->privacy->show_date_of_birth,
                'show_in_batch_list' => $this->privacy->show_in_batch_list,
            ]),

            'links' => MemberLinkResource::collection($this->whenLoaded('links')),

            'verifications' => MemberVerificationResource::collection(
                $this->whenLoaded('verifications'),
            ),

            'user' => $this->whenLoaded('user', fn (): ?array => $this->user === null ? null : [
                'email' => $this->user->email,
                'email_verified_at' => $this->user->email_verified_at?->toIso8601String(),
                'last_active_at' => $this->user->last_active_at?->toIso8601String(),
            ]),
        ];
    }
}
