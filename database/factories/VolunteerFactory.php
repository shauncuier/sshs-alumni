<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VolunteerStatus;
use App\Models\Volunteer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Volunteer>
 */
class VolunteerFactory extends Factory
{
    protected $model = Volunteer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '01'.fake()->numerify('#########'),
            'email' => fake()->safeEmail(),
            'skills' => fake()->randomElements(['Teaching', 'Management', 'Design', 'Engineering', 'Finance', 'Writing', 'Public Speaking'], 3),
            'availability' => fake()->word(),
            'location' => fake()->word(),
            'status' => fake()->randomElement(VolunteerStatus::cases()),
            'notes' => fake()->paragraph(),
            'applied_at' => null,
        ];
    }
}
