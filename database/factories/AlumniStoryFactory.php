<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StoryStatus;
use App\Models\AlumniStory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlumniStory>
 */
class AlumniStoryFactory extends Factory
{
    protected $model = AlumniStory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'author_name' => fake()->name(),
            'title' => fake()->sentence(4),
            'title_bn' => null,
            'body' => fake()->paragraph(),
            'body_bn' => null,
            'photo_path' => null,
            'career_summary' => fake()->word(),
            'status' => fake()->randomElement(StoryStatus::cases()),
            'published_at' => null,
            'meta_title' => fake()->word(),
            'meta_description' => fake()->word(),
            'og_image_path' => null,
        ];
    }
}
