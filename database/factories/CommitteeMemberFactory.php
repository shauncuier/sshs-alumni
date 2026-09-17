<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CommitteeMemberStatus;
use App\Models\Committee;
use App\Models\CommitteeMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommitteeMember>
 */
class CommitteeMemberFactory extends Factory
{
    protected $model = CommitteeMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'committee_id' => Committee::factory(),
            'name' => fake()->name(),
            'role' => fake()->jobTitle(),
            'designation' => fake()->jobTitle(),
            'photo_path' => null,
            'bio' => fake()->paragraph(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => '01'.fake()->numerify('#########'),
            'display_order' => fake()->numberBetween(1, 100),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'status' => fake()->randomElement(CommitteeMemberStatus::cases()),
        ];
    }
}
