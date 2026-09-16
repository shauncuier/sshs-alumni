<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DonationStatus;
use App\Models\Donation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    protected $model = Donation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'donor_name' => fake()->name(),
            'donor_email' => fake()->safeEmail(),
            'donor_phone' => '01'.fake()->numerify('#########'),
            'campaign' => fake()->word(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'currency' => 'BDT',
            'message' => fake()->word(),
            'message_bn' => null,
            'status' => fake()->randomElement(DonationStatus::cases()),
            'received_at' => null,
        ];
    }
}
