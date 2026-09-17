<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CrmActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One entry on the timeline.
 *
 * `subject_type` is exposed as a short label rather than a class name: the
 * frontend groups a person's member and contact entries into one feed, and
 * `App\Models\CrmContact` in a payload is an implementation detail the browser
 * has no business knowing.
 *
 * @mixin CrmActivity
 */
class CrmActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'subject_line' => $this->subject_line,
            'body' => $this->body,
            'outcome' => $this->outcome,
            'occurred_at' => $this->occurred_at->toIso8601String(),

            // Which of the person's two records this was written against, so
            // the feed can say so where it matters.
            'on' => str_contains($this->subject_type, 'Member') ? 'member' : 'contact',

            'user' => $this->whenLoaded('user', fn (): ?string => $this->user?->name),

            // `meta` carries ids and enum values for system rows. It is
            // deliberately passed through: it is written by our own observers,
            // never by a request.
            'meta' => $this->meta,
        ];
    }
}
