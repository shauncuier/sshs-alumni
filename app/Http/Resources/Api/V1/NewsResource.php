<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin News
 */
class NewsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'category' => $this->category,
            'is_featured' => $this->is_featured,
            'cover_url' => $this->cover_path ? asset('storage/'.$this->cover_path) : null,
            'published_at' => $this->published_at?->toIso8601String(),
            'views_count' => $this->views_count,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
        ];
    }
}
