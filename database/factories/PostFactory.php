<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PostCategory;
use App\Enums\PostStatus;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_member_id' => Member::factory(),
            'category' => fake()->randomElement(PostCategory::cases()),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'status' => fake()->randomElement(PostStatus::cases()),
            'last_activity_at' => null,
        ];
    }
}
