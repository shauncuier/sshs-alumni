<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SponsorTier;
use App\Models\SponsorshipPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SponsorshipPackage>
 */
class SponsorshipPackageFactory extends Factory
{
    protected $model = SponsorshipPackage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'name' => fake()->name(),
            'tier' => fake()->randomElement(SponsorTier::cases()),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'currency' => 'BDT',
            'benefits' => fake()->word(),
            'max_slots' => fake()->numberBetween(1, 100),
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }
}
