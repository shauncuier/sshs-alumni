<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MentorProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MentorProfile
 */
class MentorProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'member_id' => $this->member_id,
            'title' => $this->title,
            'company_or_institution' => $this->company_or_institution,
            'expertise' => $this->expertise,
            'bio' => $this->bio,
            'years_of_experience' => $this->years_of_experience,
            'max_mentees' => $this->max_mentees,
            'is_available' => $this->is_available,
            'member' => $this->whenLoaded('member', fn () => [
                'ulid' => $this->member->ulid,
                'full_name' => $this->member->full_name,
                'batch' => $this->member->batch?->name,
                'photo_url' => $this->member->photo_path ? asset('storage/'.$this->member->photo_path) : null,
                'membership_no' => $this->member->membership_no,
            ]),
        ];
    }
}
