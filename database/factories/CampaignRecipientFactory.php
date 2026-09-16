<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CampaignRecipientStatus;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignRecipient>
 */
class CampaignRecipientFactory extends Factory
{
    protected $model = CampaignRecipient::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'email' => fake()->safeEmail(),
            'phone' => '01'.fake()->numerify('#########'),
            'status' => fake()->randomElement(CampaignRecipientStatus::cases()),
            'sent_at' => null,
            'delivered_at' => null,
            'opened_at' => null,
            'error' => fake()->word(),
            'provider_message_id' => fake()->word(),
        ];
    }
}
