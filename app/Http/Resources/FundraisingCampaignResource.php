<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Donation;
use App\Models\FundraisingCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FundraisingCampaign
 */
class FundraisingCampaignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'slug' => $this->slug,
            'title' => $this->title,
            'tagline' => $this->tagline,
            'description' => $this->description,
            'goal_amount' => (float) $this->goal_amount,
            'raised_amount' => (float) $this->raised_amount,
            'progress_percentage' => $this->progressPercentage(),
            'is_goal_reached' => $this->isGoalReached(),
            'days_left' => $this->daysLeft(),
            'cover_image_url' => $this->cover_image_path ? asset('storage/'.$this->cover_image_path) : null,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'is_featured' => $this->is_featured,
            'status' => $this->status->value,
            'recent_donors' => $this->whenLoaded('donations', fn () => $this->donations
                ->where('is_public', true)
                ->take(10)
                ->map(fn (Donation $d) => [
                    'donor_name' => $d->is_anonymous ? 'Anonymous Alumnus' : $d->donor_name,
                    'amount' => (float) $d->amount,
                    'received_at' => $d->received_at?->diffForHumans(),
                ])
                ->values()
            ),
        ];
    }
}
