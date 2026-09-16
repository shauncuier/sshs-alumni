<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BatchStatus;
use App\Models\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    protected $model = Batch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'name' => fake()->name(),
            'name_bn' => null,
            'ssc_year' => fake()->numberBetween(1981, 2024),
            'description' => fake()->paragraph(),
            'description_bn' => null,
            'cover_path' => null,
            'status' => fake()->randomElement(BatchStatus::cases()),
        ];
    }
}
