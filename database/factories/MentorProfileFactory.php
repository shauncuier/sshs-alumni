<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\MentorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MentorProfile>
 */
class MentorProfileFactory extends Factory
{
    protected $model = MentorProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'title' => fake()->jobTitle(),
            'company_or_institution' => fake()->company(),
            'expertise' => fake()->randomElements([
                'Software Engineering', 'Higher Education Abroad', 'Civil Service & BCS',
                'Medical Sciences', 'Corporate Management', 'Entrepreneurship', 'Data & AI',
            ], 3),
            'bio' => fake()->paragraph(),
            'years_of_experience' => fake()->numberBetween(3, 20),
            'max_mentees' => 3,
            'is_available' => true,
        ];
    }
}
