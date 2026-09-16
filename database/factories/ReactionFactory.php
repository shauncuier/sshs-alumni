<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReactionType;
use App\Models\Member;
use App\Models\Post;
use App\Models\Reaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reaction>
 */
class ReactionFactory extends Factory
{
    protected $model = Reaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reactable_type' => Post::class,
            'reactable_id' => Post::factory(),
            'member_id' => Member::factory(),
            'type' => fake()->randomElement(ReactionType::cases()),
        ];
    }
}
