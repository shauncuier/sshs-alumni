<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\GalleryAlbum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryAlbum>
 */
class GalleryAlbumFactory extends Factory
{
    protected $model = GalleryAlbum::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'cover_path' => null,
            'status' => fake()->randomElement(ContentStatus::cases()),
            'published_at' => null,
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }
}
