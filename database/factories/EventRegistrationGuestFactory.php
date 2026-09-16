<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EventRegistration;
use App\Models\EventRegistrationGuest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventRegistrationGuest>
 */
class EventRegistrationGuestFactory extends Factory
{
    protected $model = EventRegistrationGuest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_registration_id' => EventRegistration::factory(),
            'name' => fake()->name(),
            'relation' => fake()->word(),
            'age_group' => fake()->word(),
            'notes' => fake()->paragraph(),
        ];
    }
}
