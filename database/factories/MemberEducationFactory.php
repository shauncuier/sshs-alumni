<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberEducation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberEducation>
 */
class MemberEducationFactory extends Factory
{
    protected $model = MemberEducation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'institution' => fake()->company(),
            'degree' => fake()->word(),
            'field_of_study' => fake()->word(),
            'start_year' => fake()->numberBetween(1, 100),
            'end_year' => fake()->numberBetween(1, 100),
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }
}
