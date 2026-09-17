<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CrmContact;
use App\Models\CrmTag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A CRM contact, as the committee sees it.
 *
 * There is no public counterpart and there never should be: this is the
 * association's working record of a person — who owns the relationship, what
 * stage it is at, internal notes. None of it is the subject's to read, and all
 * of it sits behind `crm.view`.
 *
 * @mixin CrmContact
 */
class CrmContactResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'name' => $this->name,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'organization_name' => $this->organization_name,
            'designation' => $this->designation,

            'email' => $this->email,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'address' => $this->address,
            'city' => $this->city,
            'district' => $this->district,
            'country' => $this->country,

            'source' => $this->source,
            'relationship_type' => $this->relationship_type,
            'pipeline_status' => $this->pipeline_status->value,
            'pipeline_label' => $this->pipeline_status->label(),
            'notes' => $this->notes,

            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),

            'owner' => $this->whenLoaded('owner', fn (): ?array => $this->owner === null ? null : [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ]),

            // The alumni record this contact turned out to be. The person is
            // never duplicated — see PipelineService::linkToMember().
            'member' => $this->whenLoaded('member', fn (): ?array => $this->member === null ? null : [
                'ulid' => $this->member->ulid,
                'full_name' => $this->member->full_name,
                'membership_no' => $this->member->membership_no,
                'status_label' => $this->member->status->label(),
            ]),

            'tags' => $this->whenLoaded(
                'tags',
                fn (): array => $this->tags
                    ->map(fn (CrmTag $tag): array => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'color' => $tag->color,
                    ])
                    ->values()
                    ->all(),
                [],
            ),

            'open_tasks_count' => $this->whenCounted('tasks'),
        ];
    }
}
