<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'status' => fake()->randomElement(ContentStatus::cases()),
            'meta_title' => fake()->word(),
            'meta_description' => fake()->word(),
            'og_image_path' => null,
            'published_at' => null,
        ];
    }
}
