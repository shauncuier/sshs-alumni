<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BusinessListing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BusinessListing
 */
class BusinessListingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'name' => $this->name,
            'category' => $this->category,
            'industry' => $this->industry,
            'tagline' => $this->tagline,
            'description' => $this->description,
            'address' => $this->address,
            'city' => $this->city,
            'district' => $this->district,
            'country' => $this->country,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'logo_url' => $this->logo_path ? asset('storage/'.$this->logo_path) : null,
            'alumni_discount' => $this->alumni_discount,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'owner' => $this->whenLoaded('member', fn () => [
                'ulid' => $this->member->ulid,
                'full_name' => $this->member->full_name,
                'batch' => $this->member->batch?->name,
                'membership_no' => $this->member->membership_no,
            ]),
        ];
    }
}
