<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobPosting
 */
class JobPostingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'title' => $this->title,
            'company_name' => $this->company_name,
            'location' => $this->location,
            'workplace_type' => $this->workplace_type,
            'employment_type' => $this->employment_type,
            'experience_level' => $this->experience_level,
            'salary_range' => $this->salary_range,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'application_url_or_email' => $this->application_url_or_email,
            'deadline_at' => $this->deadline_at?->format('Y-m-d'),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'poster' => $this->whenLoaded('member', fn () => [
                'ulid' => $this->member->ulid,
                'full_name' => $this->member->full_name,
                'batch' => $this->member->batch?->name,
            ]),
        ];
    }
}
