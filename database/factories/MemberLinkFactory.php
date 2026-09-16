<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MemberLinkType;
use App\Models\Member;
use App\Models\MemberLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberLink>
 */
class MemberLinkFactory extends Factory
{
    protected $model = MemberLink::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'type' => fake()->randomElement(MemberLinkType::cases()),
            'url' => fake()->url(),
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }
}
