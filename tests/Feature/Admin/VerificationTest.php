<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Enums\RelationType;
use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberVerification;
use App\Models\User;
use App\Services\Membership\MembershipNumberGenerator;
use App\Services\Membership\VerificationService;
use Database\Seeders\ProductionSeeder;

beforeEach(function (): void {
    $this->seed(ProductionSeeder::class);
});

function verifier(string $role = 'Membership Manager'): User
{
    $user = User::factory()->create();
    $user->syncRoles([$role]);

    return $user;
}

function applicant(MemberStatus $status = MemberStatus::Pending, int $sscYear = 1990): Member
{
    $batch = Batch::query()->where('ssc_year', $sscYear)->firstOrFail();

    return Member::factory()->create([
        'status' => $status,
        'batch_id' => $batch->id,
        'ssc_year' => $sscYear,
        'membership_no' => null,
    ]);
}

describe('the state machine', function (): void {
    it('allows the documented transitions', function (
        MemberStatus $from,
        MemberStatus $to,
    ): void {
        expect(app(VerificationService::class)->canTransition($from, $to))->toBeTrue();
    })->with([
        [MemberStatus::Pending, MemberStatus::UnderReview],
        [MemberStatus::Pending, MemberStatus::Approved],
        [MemberStatus::UnderReview, MemberStatus::Approved],
        [MemberStatus::UnderReview, MemberStatus::Rejected],
        [MemberStatus::Approved, MemberStatus::Suspended],
        [MemberStatus::Suspended, MemberStatus::Approved],
        [MemberStatus::Rejected, MemberStatus::UnderReview],
    ]);

    it('refuses transitions that make no sense', function (
        MemberStatus $from,
        MemberStatus $to,
    ): void {
        expect(app(VerificationService::class)->canTransition($from, $to))->toBeFalse();
    })->with([
        // An approved member is not re-approved; they are suspended or archived.
        [MemberStatus::Approved, MemberStatus::Pending],
        [MemberStatus::Approved, MemberStatus::Rejected],
        [MemberStatus::Rejected, MemberStatus::Approved],
        [MemberStatus::Suspended, MemberStatus::Pending],
    ]);

    it('throws rather than silently ignoring a bad transition', function (): void {
        $member = applicant(MemberStatus::Approved);

        expect(fn () => app(VerificationService::class)->transition(
            $member,
            MemberStatus::Pending,
            verifier(),
        ))->toThrow(InvalidArgumentException::class);
    });
});

describe('approving an application', function (): void {
    it('records who approved it and when', function (): void {
        $member = applicant();
        $actor = verifier();

        app(VerificationService::class)->transition(
            $member,
            MemberStatus::Approved,
            $actor,
        );

        $member->refresh();

        expect($member->status)->toBe(MemberStatus::Approved)
            ->and($member->verified_at)->not->toBeNull()
            ->and($member->verified_by)->toBe($actor->id);
    });

    it('issues a membership number scoped to the batch', function (): void {
        $member = applicant(sscYear: 1990);

        app(VerificationService::class)->transition(
            $member,
            MemberStatus::Approved,
            verifier(),
        );

        expect($member->refresh()->membership_no)->toBe('SSHS-1990-0001');
    });

    it('numbers members sequentially within a batch', function (): void {
        $service = app(VerificationService::class);
        $actor = verifier();

        $first = applicant(sscYear: 1995);
        $second = applicant(sscYear: 1995);

        $service->transition($first, MemberStatus::Approved, $actor);
        $service->transition($second, MemberStatus::Approved, $actor);

        expect($first->refresh()->membership_no)->toBe('SSHS-1995-0001')
            ->and($second->refresh()->membership_no)->toBe('SSHS-1995-0002');
    });

    it('starts each batch at one', function (): void {
        $service = app(VerificationService::class);
        $actor = verifier();

        $ninety = applicant(sscYear: 1990);
        $two_thousand = applicant(sscYear: 2000);

        $service->transition($ninety, MemberStatus::Approved, $actor);
        $service->transition($two_thousand, MemberStatus::Approved, $actor);

        // Scoped per batch, so both are the first of their own cohort.
        expect($ninety->refresh()->membership_no)->toBe('SSHS-1990-0001')
            ->and($two_thousand->refresh()->membership_no)->toBe('SSHS-2000-0001');
    });

    it('keeps the original number when a member is restored', function (): void {
        $service = app(VerificationService::class);
        $actor = verifier();
        $member = applicant();

        $service->transition($member, MemberStatus::Approved, $actor);
        $original = $member->refresh()->membership_no;

        $service->transition($member, MemberStatus::Suspended, $actor);
        $service->transition($member->refresh(), MemberStatus::Approved, $actor);

        expect($member->refresh()->membership_no)->toBe($original);
    });

    it('never issues the same number twice', function (): void {
        $service = app(VerificationService::class);
        $actor = verifier();

        $numbers = collect(range(1, 6))->map(function () use ($service, $actor): string {
            $member = applicant(sscYear: 1990);
            $service->transition($member, MemberStatus::Approved, $actor);

            return (string) $member->refresh()->membership_no;
        });

        expect($numbers->unique())->toHaveCount(6);
    });
});

describe('the verification history', function (): void {
    it('records every transition', function (): void {
        $service = app(VerificationService::class);
        $actor = verifier();
        $member = applicant();

        $service->transition($member, MemberStatus::UnderReview, $actor);
        $service->transition($member->refresh(), MemberStatus::Approved, $actor);

        $history = MemberVerification::query()
            ->where('member_id', $member->id)
            ->orderBy('id')
            ->get();

        expect($history)->toHaveCount(2)
            ->and($history[0]->from_status)->toBe(MemberStatus::Pending)
            ->and($history[0]->to_status)->toBe(MemberStatus::UnderReview)
            ->and($history[1]->to_status)->toBe(MemberStatus::Approved)
            ->and($history[1]->actor_id)->toBe($actor->id);
    });

    it('keeps the internal note separate from the message to the member', function (): void {
        $member = applicant();

        app(VerificationService::class)->requestCorrection(
            $member,
            verifier(),
            'Please upload a clearer photo.',
            'Third attempt; photo still blurry.',
        );

        $entry = MemberVerification::query()->latest('id')->firstOrFail();

        expect($entry->correction_requested)->toBe('Please upload a clearer photo.')
            ->and($entry->note)->toBe('Third attempt; photo still blurry.');
    });

    it('keeps the application open when a correction is requested', function (): void {
        $member = applicant();

        app(VerificationService::class)->requestCorrection(
            $member,
            verifier(),
            'Please confirm your SSC year.',
        );

        // Under review, not rejected — the committee is waiting, not refusing.
        expect($member->refresh()->status)->toBe(MemberStatus::UnderReview);
    });
});

describe('the verification endpoints', function (): void {
    it('lets a Membership Manager approve', function (): void {
        $member = applicant();

        $this->actingAs(verifier())
            ->post("/admin/members/{$member->ulid}/transition", [
                'status' => MemberStatus::Approved->value,
            ])
            ->assertRedirect();

        expect($member->refresh()->status)->toBe(MemberStatus::Approved);
    });

    it('forbids a role without members.verify', function (): void {
        $member = applicant();

        $this->actingAs(verifier('Content Manager'))
            ->post("/admin/members/{$member->ulid}/transition", [
                'status' => MemberStatus::Approved->value,
            ])
            ->assertForbidden();

        expect($member->refresh()->status)->toBe(MemberStatus::Pending);
    });

    it('forbids a member approving themselves', function (): void {
        // Whatever permissions they hold, nobody approves their own
        // application.
        $user = User::factory()->create();
        $user->syncRoles(['Membership Manager']);

        $own = Member::factory()->create([
            'user_id' => $user->id,
            'status' => MemberStatus::Pending,
        ]);

        $this->actingAs($user)
            ->post("/admin/members/{$own->ulid}/transition", [
                'status' => MemberStatus::Approved->value,
            ])
            ->assertForbidden();

        expect($own->refresh()->status)->toBe(MemberStatus::Pending);
    });

    it('reports an impossible transition instead of failing silently', function (): void {
        $member = applicant(MemberStatus::Approved);

        $this->actingAs(verifier())
            ->post("/admin/members/{$member->ulid}/transition", [
                'status' => MemberStatus::Pending->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    });

    it('requires a message when asking for a correction', function (): void {
        $member = applicant();

        $this->actingAs(verifier())
            ->post("/admin/members/{$member->ulid}/request-correction", [])
            ->assertSessionHasErrors('message');
    });

    it('rejects a membership number already in use', function (): void {
        $taken = applicant();
        app(VerificationService::class)->transition($taken, MemberStatus::Approved, verifier());

        $other = applicant();

        $this->actingAs(verifier())
            ->post("/admin/members/{$other->ulid}/membership-number", [
                'membership_no' => $taken->refresh()->membership_no,
            ])
            ->assertSessionHasErrors('membership_no');
    });

    it('generates a number when none is supplied', function (): void {
        $member = applicant();

        $this->actingAs(verifier())
            ->post("/admin/members/{$member->ulid}/membership-number", [])
            ->assertRedirect();

        expect($member->refresh()->membership_no)->not->toBeNull();
    });
});

describe('membership numbers for non-students', function (): void {
    it('groups members with no SSC year by their relation', function (): void {
        $teacher = Member::factory()->create([
            'status' => MemberStatus::Pending,
            'relation_type' => RelationType::FormerTeacher,
            'ssc_year' => null,
            'batch_id' => null,
            'membership_no' => null,
        ]);

        $number = app(MembershipNumberGenerator::class)->generate($teacher);

        // A teacher has no SSC year and must not be forced into a batch they
        // never belonged to.
        expect($number)->toStartWith('SSHS-FORM-');
    });
});

describe('the admin member list', function (): void {
    it('is readable by a role holding members.view', function (): void {
        applicant();

        $this->actingAs(verifier('Membership Manager'))
            ->get('/admin/members')
            ->assertSuccessful();
    });

    it('narrows a Batch Coordinator to their own batch', function (): void {
        $coordinated = Batch::query()->where('ssc_year', 1990)->firstOrFail();
        $other = Batch::query()->where('ssc_year', 2000)->firstOrFail();

        $user = User::factory()->create();
        $user->syncRoles(['Batch Coordinator']);

        $coordinatorMember = Member::factory()->create([
            'user_id' => $user->id,
            'status' => MemberStatus::Approved,
            'batch_id' => $coordinated->id,
        ]);
        $coordinated->coordinators()->attach($coordinatorMember->id);

        $mine = Member::factory()->create([
            'batch_id' => $coordinated->id,
            'full_name' => 'In My Batch',
        ]);
        $theirs = Member::factory()->create([
            'batch_id' => $other->id,
            'full_name' => 'Someone Elses Batch',
        ]);

        $this->actingAs($user)
            ->get('/admin/members')
            ->assertSuccessful()
            ->assertSee('In My Batch')
            ->assertDontSee('Someone Elses Batch');

        // And the record itself is out of reach, not merely off the list.
        $this->actingAs($user)
            ->get("/admin/members/{$theirs->ulid}")
            ->assertForbidden();

        $this->actingAs($user)
            ->get("/admin/members/{$mine->ulid}")
            ->assertSuccessful();
    });
});
