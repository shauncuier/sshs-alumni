<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberEmployment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberEmployment>
 */
class MemberEmploymentFactory extends Factory
{
    protected $model = MemberEmployment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'organization' => fake()->company(),
            'job_title' => fake()->jobTitle(),
            'industry' => fake()->randomElement(['Education', 'Healthcare', 'Engineering', 'Banking', 'Government', 'Business', 'Technology']),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }
}
