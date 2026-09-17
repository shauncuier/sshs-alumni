<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CrmContactType;
use App\Enums\PipelineStage;
use App\Models\CrmContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrmContact>
 */
class CrmContactFactory extends Factory
{
    protected $model = CrmContact::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(CrmContactType::cases()),
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'designation' => fake()->jobTitle(),
            'email' => fake()->safeEmail(),
            'phone' => '01'.fake()->numerify('#########'),
            'whatsapp' => '01'.fake()->numerify('#########'),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'district' => fake()->city(),
            'country' => 'Bangladesh',
            'source' => fake()->word(),
            'relationship_type' => fake()->word(),
            'pipeline_status' => fake()->randomElement(PipelineStage::cases()),
            'last_activity_at' => null,
            'notes' => fake()->paragraph(),
        ];
    }
}
