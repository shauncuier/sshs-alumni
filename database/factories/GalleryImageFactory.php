<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GalleryAlbum;
use App\Models\GalleryImage;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryImage>
 */
class GalleryImageFactory extends Factory
{
    protected $model = GalleryImage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gallery_album_id' => GalleryAlbum::factory(),
            'media_id' => Media::factory(),
            'caption' => fake()->word(),
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }
}
