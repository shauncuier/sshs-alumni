<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'certificate_no' => 'SSHS-CERT-'.fake()->year().'-'.strtoupper(fake()->unique()->lexify('??????')),
            'member_id' => Member::factory(),
            'recipient_name' => fake()->name(),
            'title' => fake()->randomElement([
                'Certificate of Appreciation',
                'Certificate of Volunteer Service',
                'Golden Jubilee Active Contribution Award',
                'Batch Leadership Recognition',
            ]),
            'description' => fake()->sentence(8),
            'type' => fake()->randomElement(['appreciation', 'volunteer', 'committee', 'achievement']),
            'event_id' => null,
            'issue_date' => fake()->date(),
            'qr_token' => fake()->unique()->sha1(),
            'metadata' => [
                'issued_by' => 'SSHS Alumni Association Executive Committee',
                'template' => 'standard_v1',
            ],
        ];
    }
}
