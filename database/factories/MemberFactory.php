<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MemberStatus;
use App\Enums\RelationType;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'membership_no' => fake()->unique()->bothify('SSHS-####-####'),
            'full_name' => fake()->name(),
            'full_name_bn' => null,
            'photo_path' => null,
            'date_of_birth' => fake()->date(),
            'gender' => fake()->randomElement(Gender::cases()),
            'blood_group' => fake()->randomElement(BloodGroup::cases()),
            'relation_type' => fake()->randomElement(RelationType::cases()),
            'ssc_year' => fake()->numberBetween(1981, 2024),
            'student_id' => fake()->word(),
            'admission_year' => fake()->numberBetween(1976, 2024),
            'group_stream' => fake()->word(),
            'section' => fake()->word(),
            'house' => fake()->word(),
            'higher_education' => fake()->word(),
            'occupation' => fake()->jobTitle(),
            'organization' => fake()->company(),
            'job_title' => fake()->jobTitle(),
            'industry' => fake()->randomElement(['Education', 'Healthcare', 'Engineering', 'Banking', 'Government', 'Business', 'Technology']),
            'business_info' => fake()->word(),
            'country' => 'Bangladesh',
            'division' => fake()->randomElement(['Chattogram', 'Dhaka', 'Khulna', 'Rajshahi', 'Sylhet', 'Barishal', 'Rangpur', 'Mymensingh']),
            'district' => fake()->city(),
            'city' => fake()->city(),
            'address' => fake()->address(),
            'mobile' => '01'.fake()->numerify('#########'),
            'whatsapp' => '01'.fake()->numerify('#########'),
            'email' => fake()->safeEmail(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => '01'.fake()->numerify('#########'),
            'bio' => fake()->paragraph(),
            'bio_bn' => null,
            'skills' => fake()->randomElements(['Teaching', 'Management', 'Design', 'Engineering', 'Finance', 'Writing', 'Public Speaking'], 3),
            'interests' => fake()->randomElements(['Sports', 'Music', 'Literature', 'Travel', 'Photography', 'Volunteering'], 2),
            'status' => fake()->randomElement(MemberStatus::cases()),
            'verified_at' => null,
            'registered_at' => null,
            'profile_completion' => fake()->numberBetween(1, 100),
            'search_blob' => null,
        ];
    }

    /**
     * An approved member — the state most tests actually need, since a random
     * status makes a directory or batch assertion flaky.
     */
    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => MemberStatus::Approved,
            'verified_at' => now(),
        ]);
    }
}
