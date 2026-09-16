<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\MembershipFee;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => fake()->unique()->bothify('??-########'),
            'payable_type' => MembershipFee::class,
            'payable_id' => MembershipFee::factory(),
            'payer_name' => fake()->name(),
            'gateway' => fake()->word(),
            'gateway_txn_id' => fake()->word(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'currency' => 'BDT',
            'status' => fake()->randomElement(PaymentStatus::cases()),
            'paid_at' => null,
            'receipt_no' => fake()->unique()->bothify('??-########'),
            'invoice_no' => fake()->unique()->bothify('??-########'),
            'refunded_at' => null,
            'refund_reason' => fake()->word(),
            'notes' => fake()->paragraph(),
            'meta' => [],
        ];
    }
}
