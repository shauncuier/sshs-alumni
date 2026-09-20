<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\GalleryAlbum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GalleryAlbum
 */
class GalleryResource extends JsonResource
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
            'description' => $this->description,
            'cover_url' => $this->cover_path ? asset('storage/'.$this->cover_path) : null,
            'images_count' => $this->images_count,
            'images' => $this->whenLoaded('images', fn (): array => $this->images->map(fn ($image): array => [
                'id' => $image->id,
                'url' => asset('storage/'.$image->path),
                'thumb_url' => $image->thumb_path ? asset('storage/'.$image->thumb_path) : asset('storage/'.$image->path),
                'caption' => $image->caption,
                'sort_order' => $image->sort_order,
            ])->all()),
        ];
    }
}
