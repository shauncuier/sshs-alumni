<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CommitteeType;
use App\Models\Committee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Committee>
 */
class CommitteeFactory extends Factory
{
    protected $model = Committee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'name' => fake()->name(),
            'name_bn' => null,
            'type' => fake()->randomElement(CommitteeType::cases()),
            'description' => fake()->paragraph(),
            'description_bn' => null,
            'term_start' => fake()->date(),
            'term_end' => fake()->date(),
            'status' => fake()->word(),
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }
}
