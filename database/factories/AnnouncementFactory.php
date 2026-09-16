<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnnouncementKind;
use App\Enums\AnnouncementLevel;
use App\Enums\AudienceScope;
use App\Enums\ContentStatus;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => fake()->randomElement(AnnouncementKind::cases()),
            'title' => fake()->sentence(4),
            'title_bn' => null,
            'body' => fake()->paragraph(),
            'body_bn' => null,
            'level' => fake()->randomElement(AnnouncementLevel::cases()),
            'audience' => fake()->randomElement(AudienceScope::cases()),
            'starts_at' => null,
            'ends_at' => null,
            'attachment_path' => null,
            'status' => fake()->randomElement(ContentStatus::cases()),
        ];
    }
}
