<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\MemberVerification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberVerification>
 */
class MemberVerificationFactory extends Factory
{
    protected $model = MemberVerification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'from_status' => fake()->randomElement(MemberStatus::cases()),
            'to_status' => fake()->randomElement(MemberStatus::cases()),
            'note' => fake()->paragraph(),
            'correction_requested' => fake()->word(),
        ];
    }
}
