<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FeeStatus;
use App\Models\Member;
use App\Models\MembershipFee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipFee>
 */
class MembershipFeeFactory extends Factory
{
    protected $model = MembershipFee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'period_label' => (string) fake()->numberBetween(2020, 2026),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'currency' => 'BDT',
            'due_at' => fake()->date(),
            'status' => fake()->randomElement(FeeStatus::cases()),
            'waived_reason' => fake()->word(),
        ];
    }
}
