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
            // Never Batch by default: a batch post with no `batch_id` would
            // be a cohort-only post visible to everybody, which is the one
            // combination the visibility rule is there to prevent. forBatch()
            // sets both together.
            'category' => fake()->randomElement([
                PostCategory::General,
                PostCategory::Reunion,
                PostCategory::Memories,
                PostCategory::Career,
                PostCategory::Jubilee,
            ]),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            // Published by default. A factory that picks a random status
            // makes every feed test flaky for a reason that has nothing to do
            // with what it is testing.
            'status' => PostStatus::Published,
            'last_activity_at' => null,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (): array => ['status' => PostStatus::Hidden]);
    }

    public function removed(): static
    {
        return $this->state(fn (): array => ['status' => PostStatus::Removed]);
    }

    /**
     * A batch discussion post, visible only to that cohort.
     */
    public function forBatch(int $batchId): static
    {
        return $this->state(fn (): array => [
            'category' => PostCategory::Batch,
            'batch_id' => $batchId,
        ]);
    }
}
