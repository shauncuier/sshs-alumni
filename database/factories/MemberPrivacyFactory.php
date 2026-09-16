<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberPrivacy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberPrivacy>
 */
class MemberPrivacyFactory extends Factory
{
    protected $model = MemberPrivacy::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
        ];
    }
}
