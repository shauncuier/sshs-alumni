<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CampaignChannel;
use App\Models\MessageTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageTemplate>
 */
class MessageTemplateFactory extends Factory
{
    protected $model = MessageTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'name' => fake()->name(),
            'channel' => fake()->randomElement(CampaignChannel::cases()),
            'subject' => fake()->word(),
            'body' => fake()->paragraph(),
            'variables' => [],
        ];
    }
}
