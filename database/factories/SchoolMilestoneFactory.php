<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SchoolMilestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolMilestone>
 */
class SchoolMilestoneFactory extends Factory
{
    protected $model = SchoolMilestone::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'year' => fake()->numberBetween(1976, 2026),
            'date_label' => fake()->word(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'image_path' => null,
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }
}
