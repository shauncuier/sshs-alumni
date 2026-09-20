<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\JobPosting;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPosting>
 */
class JobPostingFactory extends Factory
{
    protected $model = JobPosting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'title' => fake()->jobTitle(),
            'company_name' => fake()->company(),
            'location' => fake()->city(),
            'workplace_type' => fake()->randomElement(['on_site', 'remote', 'hybrid']),
            'employment_type' => fake()->randomElement(['full_time', 'part_time', 'contract', 'internship']),
            'experience_level' => fake()->randomElement(['entry', 'mid', 'senior', 'lead']),
            'salary_range' => 'BDT 50,000 - 80,000',
            'description' => fake()->paragraphs(2, true),
            'requirements' => fake()->paragraph(),
            'application_url_or_email' => fake()->companyEmail(),
            'deadline_at' => now()->addMonth()->format('Y-m-d'),
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ];
    }
}
