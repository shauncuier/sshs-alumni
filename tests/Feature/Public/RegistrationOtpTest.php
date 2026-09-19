<?php

declare(strict_types=1);

use App\Actions\Membership\RegisterMember;
use App\Enums\RelationType;
use App\Models\Batch;
use App\Models\User;
use App\Services\Communication\PhoneVerificationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);

    config([
        'sms.driver' => 'log',
        'sms.enabled' => true,
        'sms.otp.brand' => 'SSHS Alumni',
    ]);
});

describe('Phone OTP dispatch', function (): void {
    it('dispatches an OTP to a valid Bangladeshi phone number', function (): void {
        $response = $this->postJson(route('join.otp.send'), [
            'mobile' => '01712345678',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'cooldown' => 60,
            ]);

        expect($response->json('debug_code'))->not->toBeEmpty();
    });

    it('rejects an invalid phone number format', function (): void {
        $response = $this->postJson(route('join.otp.send'), [
            'mobile' => 'invalid-phone',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    });

    it('enforces a 60-second cooldown between OTP requests', function (): void {
        $phone = '01812345678';

        $first = $this->postJson(route('join.otp.send'), ['mobile' => $phone]);
        $first->assertOk();

        $second = $this->postJson(route('join.otp.send'), ['mobile' => $phone]);
        $second->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    });
});

describe('Phone OTP verification', function (): void {
    it('verifies a valid OTP code and stores verification in session', function (): void {
        $phone = '01912345678';

        $sendResponse = $this->postJson(route('join.otp.send'), ['mobile' => $phone]);
        $sendResponse->assertOk();
        $code = $sendResponse->json('debug_code');

        $verifyResponse = $this->postJson(route('join.otp.verify'), [
            'mobile' => $phone,
            'code' => $code,
        ]);

        $verifyResponse->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $verified = session('registration.phone_verified');
        expect($verified)->not->toBeNull()
            ->and($verified['phone'])->toBe('8801912345678');
    });

    it('rejects an incorrect OTP code', function (): void {
        $phone = '01799999999';

        $this->postJson(route('join.otp.send'), ['mobile' => $phone])->assertOk();

        $verifyResponse = $this->postJson(route('join.otp.verify'), [
            'mobile' => $phone,
            'code' => '000000',
        ]);

        $verifyResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        expect(session('registration.phone_verified'))->toBeNull();
    });

    it('rejects expired or non-existent OTP requests', function (): void {
        $verifyResponse = $this->postJson(route('join.otp.verify'), [
            'mobile' => '01711111111',
            'code' => '123456',
        ]);

        $verifyResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    });
});

describe('Onboarding step integration', function (): void {
    it('blocks the basic step submission when phone is not verified', function (): void {
        $response = $this->post(route('join.store', ['step' => 'basic']), [
            'full_name' => 'Rahim Uddin',
            'relation_type' => RelationType::FormerStudent->value,
            'mobile' => '01712345678',
            'email' => 'rahim@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['mobile']);
    });

    it('blocks basic step submission if submitted phone does not match verified phone', function (): void {
        $verifiedPhone = '01712345678';
        $normalized = app(PhoneVerificationService::class)->normalise($verifiedPhone);

        session()->put('registration.phone_verified', [
            'phone' => $normalized,
            'raw' => $verifiedPhone,
            'verified_at' => now()->toIso8601String(),
        ]);

        $response = $this->post(route('join.store', ['step' => 'basic']), [
            'full_name' => 'Rahim Uddin',
            'relation_type' => RelationType::FormerStudent->value,
            'mobile' => '01899999999', // different phone
            'email' => 'rahim@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['mobile']);
    });

    it('accepts basic step submission when phone is verified and records state in draft', function (): void {
        $phone = '01712345678';
        $normalized = app(PhoneVerificationService::class)->normalise($phone);

        session()->put('registration.phone_verified', [
            'phone' => $normalized,
            'raw' => $phone,
            'verified_at' => now()->toIso8601String(),
        ]);

        $response = $this->post(route('join.store', ['step' => 'basic']), [
            'full_name' => 'Rahim Uddin',
            'relation_type' => RelationType::FormerStudent->value,
            'mobile' => $phone,
            'email' => 'rahim@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('join.step', ['step' => 'academic']));

        $draft = session('registration.draft');
        expect($draft['phone_verified'])->toBeTrue()
            ->and($draft['mobile'])->toBe($phone);
    });

    it('sets user phone_verified_at when member registration completes', function (): void {
        $batch = Batch::factory()->create();

        $draft = [
            'full_name' => 'Karim Ahmed',
            'email' => 'karim@example.com',
            'mobile' => '01712345678',
            'phone_verified' => true,
            'relation_type' => RelationType::FormerStudent->value,
            'batch_id' => $batch->id,
            'ssc_year' => $batch->ssc_year,
            'country' => 'Bangladesh',
            'privacy' => ['show_profile' => true],
            'completed_steps' => ['basic', 'academic', 'professional', 'location', 'review'],
        ];

        $hashedPassword = Hash::make('Password123!');
        $register = app(RegisterMember::class);

        $member = $register($draft, $hashedPassword);

        $user = User::query()->where('email', 'karim@example.com')->firstOrFail();
        expect($user->phone)->toBe('01712345678')
            ->and($user->phone_verified_at)->not->toBeNull()
            ->and($member->user_id)->toBe($user->id);
    });
});
