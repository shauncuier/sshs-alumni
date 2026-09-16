<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\CrmTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrmTask>
 */
class CrmTaskFactory extends Factory
{
    protected $model = CrmTask::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'due_at' => null,
            'status' => fake()->randomElement(TaskStatus::cases()),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'completed_at' => null,
        ];
    }
}
