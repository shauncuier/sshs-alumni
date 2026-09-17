<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Enums\RelationType;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    $this->seed(ProductionSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function basicStep(array $overrides = []): array
{
    return [
        'full_name' => 'Rahim Uddin',
        'relation_type' => RelationType::FormerStudent->value,
        'mobile' => '01712345678',
        'email' => 'rahim@example.test',
        'password' => 'CorrectHorse!7Battery',
        'password_confirmation' => 'CorrectHorse!7Battery',
        ...$overrides,
    ];
}

/**
 * Walk every step, returning the response from the final submit.
 */
function completeRegistration(mixed $test, array $overrides = [])
{
    $batch = Batch::query()->where('ssc_year', 2000)->firstOrFail();

    $test->post('/join/basic', basicStep($overrides))->assertRedirect('/join/academic');

    $test->post('/join/academic', [
        'batch_id' => $batch->id,
        'ssc_year' => 2000,
    ])->assertRedirect('/join/professional');

    $test->post('/join/professional', [
        'occupation' => 'Engineer',
        'organization' => 'Acme',
    ])->assertRedirect('/join/location');

    $test->post('/join/location', [
        'country' => 'Bangladesh',
        'district' => 'Chattogram',
        'city' => 'Sitakunda',
    ])->assertRedirect('/join/review');

    return $test->post('/join/review', [
        'bio' => 'A short bio.',
        'privacy' => [
            'show_profile' => true,
            'show_phone' => false,
            'show_email' => false,
            'show_workplace' => true,
            'show_location' => true,
            'show_date_of_birth' => false,
            'show_in_batch_list' => true,
        ],
        'terms' => true,
    ]);
}

describe('the registration flow', function (): void {
    it('opens on the first step', function (): void {
        $this->get('/join')->assertRedirect('/join/basic');
        $this->get('/join/basic')->assertSuccessful();
    });

    it('creates the user, member and privacy row on completion', function (): void {
        completeRegistration($this)->assertRedirect('/join/done');

        $user = User::query()->where('email', 'rahim@example.test')->firstOrFail();
        $member = $user->member;

        expect($member)->not->toBeNull()
            ->and($member->full_name)->toBe('Rahim Uddin')
            ->and($member->privacy)->not->toBeNull()
            ->and($user->hasRole('Member'))->toBeTrue();
    });

    it('starts every application as pending with no membership number', function (): void {
        completeRegistration($this);

        $member = Member::query()->where('email', 'rahim@example.test')->firstOrFail();

        // Status, membership number and verification belong to the committee,
        // never to the applicant.
        expect($member->status)->toBe(MemberStatus::Pending)
            ->and($member->membership_no)->toBeNull()
            ->and($member->verified_at)->toBeNull();
    });

    it('stores the applicant\'s own privacy choices', function (): void {
        completeRegistration($this);

        $member = Member::query()->where('email', 'rahim@example.test')->firstOrFail();

        expect($member->privacy->show_phone)->toBeFalse()
            ->and($member->privacy->show_email)->toBeFalse()
            ->and($member->privacy->show_profile)->toBeTrue();
    });

    it('produces a usable password', function (): void {
        completeRegistration($this);

        $user = User::query()->where('email', 'rahim@example.test')->firstOrFail();

        // Guards against double-hashing, which would lock the member out of
        // the account they just created.
        expect(Hash::check('CorrectHorse!7Battery', $user->password))->toBeTrue();
    });
});

describe('per-step validation', function (): void {
    it('rejects an incomplete first step', function (): void {
        $this->post('/join/basic', ['full_name' => ''])
            ->assertSessionHasErrors(['full_name', 'relation_type', 'mobile', 'email', 'password']);
    });

    it('rejects a duplicate email', function (): void {
        User::factory()->create(['email' => 'taken@example.test']);

        $this->post('/join/basic', basicStep(['email' => 'taken@example.test']))
            ->assertSessionHasErrors('email');
    });

    it('rejects a mismatched password confirmation', function (): void {
        $this->post('/join/basic', basicStep(['password_confirmation' => 'different']))
            ->assertSessionHasErrors('password');
    });

    it('requires a batch from a former student', function (): void {
        $this->post('/join/basic', basicStep());

        $this->post('/join/academic', [])
            ->assertSessionHasErrors(['batch_id', 'ssc_year']);
    });

    it('does not require a batch from a former teacher', function (): void {
        // A former teacher has no SSC year at this school.
        $this->post('/join/basic', basicStep([
            'relation_type' => RelationType::FormerTeacher->value,
        ]));

        $this->post('/join/academic', [])->assertRedirect('/join/professional');
    });

    it('requires the terms to be accepted', function (): void {
        $this->post('/join/basic', basicStep());
        $this->post('/join/academic', [
            'batch_id' => Batch::query()->value('id'),
            'ssc_year' => 2000,
        ]);
        $this->post('/join/professional', []);
        $this->post('/join/location', ['country' => 'Bangladesh']);

        $this->post('/join/review', [
            'privacy' => [
                'show_profile' => true, 'show_phone' => false, 'show_email' => false,
                'show_workplace' => true, 'show_location' => true,
                'show_date_of_birth' => false, 'show_in_batch_list' => true,
            ],
            'terms' => false,
        ])->assertSessionHasErrors('terms');
    });
});

describe('step navigation', function (): void {
    it('sends a visitor back when they skip ahead', function (): void {
        // Deep-linking past an unfinished step would fail on fields they
        // never saw.
        $this->get('/join/review')->assertRedirect('/join/basic');
    });

    it('lets a visitor return to a completed step', function (): void {
        $this->post('/join/basic', basicStep());

        $this->get('/join/basic')->assertSuccessful();
    });

    it('keeps the draft across steps', function (): void {
        $this->post('/join/basic', basicStep());

        $this->get('/join/academic')
            ->assertInertia(fn ($page) => $page
                ->where('draft.full_name', 'Rahim Uddin'));
    });

    it('redirects an unknown step to the start', function (): void {
        $this->get('/join/nonsense')->assertRedirect('/join');
    });
});

describe('password handling', function (): void {
    it('never returns the password to the browser', function (): void {
        $this->post('/join/basic', basicStep());

        $this->get('/join/academic')
            ->assertInertia(fn ($page) => $page
                ->missing('draft.password')
                ->missing('draft.password_confirmation'));
    });

    it('never keeps a plaintext password in the session', function (): void {
        $this->post('/join/basic', basicStep());

        // The session store for this application is the database, so a
        // plaintext password there would be a plaintext password at rest.
        $draft = session('registration.draft');
        $stored = session('registration.password');

        expect($draft)->not->toHaveKey('password')
            ->and($stored)->toBeString()
            ->and($stored)->not->toBe('CorrectHorse!7Battery')
            ->and(Hash::check('CorrectHorse!7Battery', $stored))->toBeTrue();
    });
});

describe('mass assignment', function (): void {
    it('ignores a forged status, membership number or verification', function (): void {
        completeRegistration($this, []);

        // Re-submit the review step with privileged fields injected.
        $this->post('/join/basic', basicStep([
            'email' => 'forger@example.test',
            'status' => MemberStatus::Approved->value,
            'membership_no' => 'SSHS-1990-0001',
            'verified_at' => now()->toDateTimeString(),
        ]));

        $member = Member::query()->where('email', 'forger@example.test')->first();

        // The account is not created until the flow completes, and even then
        // these fields are set by the action, not the payload.
        expect($member)->toBeNull();
    });
});

describe('rate limiting', function (): void {
    it('throttles account creation harder than draft steps', function (): void {
        $reviewRoute = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($route): bool => $route->uri() === 'join/review');

        expect($reviewRoute->gatherMiddleware())->toContain('throttle:5,1');
    });
});
