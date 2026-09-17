<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\VolunteerTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VolunteerTeam>
 */
class VolunteerTeamFactory extends Factory
{
    protected $model = VolunteerTeam::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'name' => fake()->name(),
            'description' => fake()->paragraph(),
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }
}
