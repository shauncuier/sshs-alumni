<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CrmActivityType;
use App\Models\CrmActivity;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrmActivity>
 */
class CrmActivityFactory extends Factory
{
    protected $model = CrmActivity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_type' => Member::class,
            'subject_id' => Member::factory(),
            'type' => fake()->randomElement(CrmActivityType::cases()),
            'subject_line' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'outcome' => fake()->word(),
            'occurred_at' => now(),
            'meta' => [],
        ];
    }
}
