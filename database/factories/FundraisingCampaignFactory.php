<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\FundraisingCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FundraisingCampaignFactory>
 */
class FundraisingCampaignFactory extends Factory
{
    protected $model = FundraisingCampaign::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'title' => fake()->sentence(4),
            'tagline' => fake()->sentence(6),
            'description' => fake()->paragraphs(3, true),
            'goal_amount' => 500000.00,
            'raised_amount' => 150000.00,
            'cover_image_path' => null,
            'starts_at' => now()->subWeeks(2),
            'ends_at' => now()->addWeeks(6),
            'is_featured' => true,
            'status' => ContentStatus::Published,
            'published_at' => now()->subWeeks(2),
        ];
    }
}
