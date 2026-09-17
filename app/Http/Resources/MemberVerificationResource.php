<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MemberVerification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One entry in a member's verification history.
 *
 * `note` is the committee's INTERNAL note and is only ever serialised for
 * administrators. `correction_requested` is the message that was sent to the
 * member, so it is safe either way.
 *
 * @mixin MemberVerification
 */
class MemberVerificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_status' => $this->from_status?->value,
            'from_label' => $this->from_status?->label(),
            'to_status' => $this->to_status->value,
            'to_label' => $this->to_status->label(),
            'note' => $this->note,
            'correction_requested' => $this->correction_requested,
            'actor' => $this->whenLoaded(
                'actor',
                fn (): ?string => $this->actor?->name,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
