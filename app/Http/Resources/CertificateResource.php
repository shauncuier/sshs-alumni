<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Certificate
 */
class CertificateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'certificate_no' => $this->certificate_no,
            'recipient_name' => $this->recipient_name,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'event' => $this->whenLoaded('event', fn () => [
                'ulid' => $this->event->ulid,
                'title' => $this->event->title,
            ]),
            'member' => $this->whenLoaded('member', fn () => [
                'ulid' => $this->member->ulid,
                'full_name' => $this->member->full_name,
                'membership_no' => $this->member->membership_no,
                'batch' => $this->member->batch?->name,
            ]),
        ];
    }
}
