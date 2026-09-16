<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventCheckin;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventCheckin>
 */
class EventCheckinFactory extends Factory
{
    protected $model = EventCheckin::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_registration_id' => EventRegistration::factory(),
            'event_id' => Event::factory(),
            'checked_in_at' => now(),
            'operator_id' => User::factory(),
            'gate' => fake()->word(),
            'device' => fake()->word(),
        ];
    }
}
