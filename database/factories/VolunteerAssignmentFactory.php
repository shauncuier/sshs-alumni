<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AssignmentStatus;
use App\Models\Volunteer;
use App\Models\VolunteerAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VolunteerAssignment>
 */
class VolunteerAssignmentFactory extends Factory
{
    protected $model = VolunteerAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'volunteer_id' => Volunteer::factory(),
            'responsibility' => fake()->word(),
            'shift_start' => null,
            'shift_end' => null,
            'status' => fake()->randomElement(AssignmentStatus::cases()),
        ];
    }
}
