<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\MentorshipRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MentorshipRequest>
 */
class MentorshipRequestFactory extends Factory
{
    protected $model = MentorshipRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mentor_id' => Member::factory(),
            'mentee_id' => Member::factory(),
            'topic' => 'Career Guidance in Tech',
            'message' => fake()->paragraph(),
            'status' => 'pending',
            'response_note' => null,
            'responded_at' => null,
        ];
    }
}
