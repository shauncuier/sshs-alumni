<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The member as an anonymous visitor may see them — the QR verification page,
 * and nothing else.
 *
 * THIS RESOURCE IGNORES PRIVACY FLAGS, DELIBERATELY, AND IN THE SAFE
 * DIRECTION. It exposes the six fields needed to confirm a membership card is
 * genuine and no more. A member who has switched every privacy flag ON still
 * does not widen this list, because the card-verification page has a single
 * purpose and does not become a public profile.
 *
 * @see docs/08-security-privacy.md section 2
 *
 * @mixin Member
 */
class PublicMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'full_name' => $this->full_name,
            'photo_url' => $this->photo_path === null
                ? null
                : asset('storage/'.$this->photo_path),
            'membership_no' => $this->membership_no,
            'batch' => $this->whenLoaded(
                'batch',
                fn (): ?string => $this->batch?->name,
            ),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_verified' => $this->isApproved(),
            'verified_at' => $this->verified_at?->toDateString(),
        ];
    }
}
