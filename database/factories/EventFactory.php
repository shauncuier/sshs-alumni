<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventDateStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'type' => fake()->randomElement(EventType::cases()),
            'title' => fake()->sentence(4),
            'title_bn' => null,
            'summary' => fake()->sentence(12),
            'summary_bn' => null,
            'description' => fake()->paragraph(),
            'description_bn' => null,
            'cover_path' => null,
            'date_status' => fake()->randomElement(EventDateStatus::cases()),
            'starts_at' => null,
            'ends_at' => null,
            'venue' => fake()->word(),
            'venue_bn' => null,
            'address' => fake()->address(),
            'map_url' => fake()->url(),
            'latitude' => fake()->randomFloat(2, 100, 10000),
            'longitude' => fake()->randomFloat(2, 100, 10000),
            'registration_opens_at' => null,
            'registration_closes_at' => null,
            'capacity' => fake()->numberBetween(1, 100),
            'registration_fee' => fake()->randomFloat(2, 100, 10000),
            'currency' => 'BDT',
            'organizer_name' => fake()->name(),
            'contact_phone' => '01'.fake()->numerify('#########'),
            'contact_email' => fake()->safeEmail(),
            'status' => fake()->randomElement(EventStatus::cases()),
            'meta_title' => fake()->word(),
            'meta_description' => fake()->word(),
            'og_image_path' => null,
            'published_at' => null,
        ];
    }
}
