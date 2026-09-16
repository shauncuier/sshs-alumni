<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AudienceType;
use App\Enums\CampaignChannel;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'channel' => fake()->randomElement(CampaignChannel::cases()),
            'subject' => fake()->word(),
            'subject_bn' => null,
            'body' => fake()->paragraph(),
            'body_bn' => null,
            'audience_type' => fake()->randomElement(AudienceType::cases()),
            'audience_filters' => [],
            'scheduled_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'status' => fake()->randomElement(CampaignStatus::cases()),
            'segments_per_message' => fake()->numberBetween(1, 100),
            'estimated_cost' => fake()->randomFloat(2, 100, 10000),
        ];
    }
}
