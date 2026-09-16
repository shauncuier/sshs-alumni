<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'action' => fake()->word(),
            'before' => [],
            'after' => [],
            'description' => fake()->paragraph(),
            'ip_address' => fake()->word(),
            'user_agent' => fake()->word(),
        ];
    }
}
