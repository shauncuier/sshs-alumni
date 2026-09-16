<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventTicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventTicketType>
 */
class EventTicketTypeFactory extends Factory
{
    protected $model = EventTicketType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->name(),
            'name_bn' => null,
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 100, 10000),
            'currency' => 'BDT',
            'quantity' => fake()->numberBetween(1, 100),
            'per_person_limit' => fake()->numberBetween(1, 100),
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }
}
