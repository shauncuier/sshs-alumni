<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\News;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<News>
 */
class NewsFactory extends Factory
{
    protected $model = News::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'title' => fake()->sentence(4),
            'excerpt' => fake()->sentence(12),
            'body' => fake()->paragraph(),
            'cover_path' => null,
            'category' => fake()->word(),
            'status' => fake()->randomElement(ContentStatus::cases()),
            'published_at' => null,
            'meta_title' => fake()->word(),
            'meta_description' => fake()->word(),
            'og_image_path' => null,
        ];
    }
}
