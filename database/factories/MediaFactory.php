<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MediaCollection;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collection' => fake()->randomElement(MediaCollection::cases()),
            'disk' => 'public',
            'path' => 'demo/'.fake()->uuid().'.jpg',
            'thumb_path' => null,
            'original_name' => fake()->name(),
            'mime_type' => fake()->word(),
            'extension' => 'jpg',
            'size' => fake()->numberBetween(1, 100),
            'width' => fake()->numberBetween(1, 100),
            'height' => fake()->numberBetween(1, 100),
            'alt' => fake()->word(),
        ];
    }
}
