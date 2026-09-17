<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\TaskStatus;
use App\Models\CrmContact;
use App\Models\CrmTask;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A follow-up.
 *
 * `is_overdue` is computed here rather than in the browser, because "overdue"
 * depends on the server's clock and a device with a wrong date should not be
 * able to hide a task that is late.
 *
 * @mixin CrmTask
 */
class CrmTaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'due_at' => $this->due_at?->toIso8601String(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'completed_at' => $this->completed_at?->toIso8601String(),

            'is_overdue' => $this->due_at !== null
                && $this->due_at->isPast()
                && ! in_array($this->status, [TaskStatus::Done, TaskStatus::Cancelled], true),

            'assignee' => $this->whenLoaded('assignee', fn (): ?array => $this->assignee === null ? null : [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
            ]),

            'creator' => $this->whenLoaded('creator', fn (): ?string => $this->creator?->name),

            // What the task is about, if anything. A task may stand alone —
            // "book the hall" belongs to nobody in particular.
            'subject' => $this->whenLoaded('subject', fn (): ?array => match (true) {
                $this->subject instanceof CrmContact => [
                    'kind' => 'contact',
                    'ulid' => $this->subject->ulid,
                    'name' => $this->subject->name,
                ],
                $this->subject instanceof Member => [
                    'kind' => 'member',
                    'ulid' => $this->subject->ulid,
                    'name' => $this->subject->full_name,
                ],
                default => null,
            }),
        ];
    }
}
