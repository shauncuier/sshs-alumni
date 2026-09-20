<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MentorshipRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MentorshipRequest
 */
class MentorshipRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'topic' => $this->topic,
            'message' => $this->message,
            'status' => $this->status,
            'response_note' => $this->response_note,
            'responded_at' => $this->responded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'mentor' => $this->whenLoaded('mentor', fn () => [
                'ulid' => $this->mentor->ulid,
                'full_name' => $this->mentor->full_name,
                'batch' => $this->mentor->batch?->name,
            ]),
            'mentee' => $this->whenLoaded('mentee', fn () => [
                'ulid' => $this->mentee->ulid,
                'full_name' => $this->mentee->full_name,
                'batch' => $this->mentee->batch?->name,
                'email' => $this->mentee->email,
                'mobile' => $this->mentee->mobile,
            ]),
        ];
    }
}
