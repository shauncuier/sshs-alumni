<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FaqGroup;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group' => fake()->randomElement(FaqGroup::cases()),
            'question' => fake()->sentence(8).'?',
            'question_bn' => null,
            'answer' => fake()->paragraph(),
            'answer_bn' => null,
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }
}
