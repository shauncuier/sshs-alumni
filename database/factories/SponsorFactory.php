<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SponsorKind;
use App\Enums\SponsorStatus;
use App\Models\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sponsor>
 */
class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => fake()->randomElement(SponsorKind::cases()),
            'name' => fake()->name(),
            'name_bn' => null,
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => '01'.fake()->numerify('#########'),
            'logo_path' => null,
            'website' => fake()->url(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'currency' => 'BDT',
            'agreement_path' => null,
            'status' => fake()->randomElement(SponsorStatus::cases()),
            'display_order' => fake()->numberBetween(1, 100),
            'notes' => fake()->paragraph(),
        ];
    }
}
