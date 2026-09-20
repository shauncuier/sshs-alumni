<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\BusinessListing;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessListing>
 */
class BusinessListingFactory extends Factory
{
    protected $model = BusinessListing::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'name' => fake()->company(),
            'category' => fake()->randomElement(['Technology', 'Consulting', 'Healthcare', 'Education', 'Legal', 'Retail', 'Food & Beverage']),
            'industry' => fake()->word(),
            'tagline' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'district' => 'Dhaka',
            'country' => 'Bangladesh',
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'website' => fake()->url(),
            'logo_path' => null,
            'alumni_discount' => '10% discount for SSHS alumni',
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ];
    }
}
