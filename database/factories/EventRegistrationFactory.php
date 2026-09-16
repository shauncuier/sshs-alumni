<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventRegistration>
 */
class EventRegistrationFactory extends Factory
{
    protected $model = EventRegistration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'registrant_name' => fake()->name(),
            'registrant_email' => fake()->safeEmail(),
            'registrant_phone' => '01'.fake()->numerify('#########'),
            'amount_due' => fake()->randomFloat(2, 100, 10000),
            'currency' => 'BDT',
            'payment_status' => fake()->randomElement(PaymentStatus::cases()),
            'status' => fake()->randomElement(RegistrationStatus::cases()),
            'qr_token' => fake()->unique()->sha1(),
            'registered_at' => null,
            'notes' => fake()->paragraph(),
        ];
    }
}
