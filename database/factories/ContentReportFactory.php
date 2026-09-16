<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\ContentReport;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentReport>
 */
class ContentReportFactory extends Factory
{
    protected $model = ContentReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reportable_type' => Post::class,
            'reportable_id' => Post::factory(),
            'reason' => fake()->randomElement(ReportReason::cases()),
            'note' => fake()->paragraph(),
            'status' => fake()->randomElement(ReportStatus::cases()),
            'resolved_at' => null,
            'resolution_note' => fake()->word(),
        ];
    }
}
