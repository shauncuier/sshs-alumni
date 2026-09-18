<?php

declare(strict_types=1);

use App\Enums\AssignmentStatus;
use App\Enums\CommitteeMemberStatus;
use App\Enums\CrmActivityType;
use App\Enums\DonationStatus;
use App\Enums\FeeStatus;
use App\Enums\MemberStatus;
use App\Models\Committee;
use App\Models\CommitteeMember;
use App\Models\CrmActivity;
use App\Models\Donation;
use App\Models\Member;
use App\Models\MembershipFee;
use App\Models\Payment;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\VolunteerTeam;
use App\Services\Payments\PaymentRecorder;
use Database\Seeders\RolePermissionSeeder;

/**
 * Fees, volunteers and committees.
 *
 * The important one is waiving: a volunteer-run association waives fees
 * routinely, and recording that as a payment would put money in the ledger
 * that nobody ever received.
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function financeManager(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Finance Manager']);

    return $user;
}

function volunteerCoordinator(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Volunteer Coordinator']);

    return $user;
}

describe('raising fees', function (): void {
    it('covers every approved member and skips the rest', function (): void {
        Member::factory()->approved()->count(3)->create();
        Member::factory()->count(2)->create(['status' => MemberStatus::Pending]);

        $this->actingAs(financeManager())
            ->post('/admin/fees/generate', [
                'period_label' => '2026',
                'amount' => 500,
            ])
            ->assertRedirect();

        expect(MembershipFee::query()->count())->toBe(3);
    });

    it('is safe to run again', function (): void {
        Member::factory()->approved()->count(2)->create();

        $payload = ['period_label' => '2026', 'amount' => 500];
        $actor = financeManager();

        $this->actingAs($actor)->post('/admin/fees/generate', $payload);

        // A member who joined in between gets one; the rest are untouched.
        Member::factory()->approved()->create();

        $this->actingAs($actor)->post('/admin/fees/generate', $payload);

        expect(MembershipFee::query()->count())->toBe(3);
    });
});

describe('waiving a fee', function (): void {
    it('puts NO money in the ledger', function (): void {
        $fee = MembershipFee::factory()->create([
            'amount' => 500,
            'status' => FeeStatus::Pending,
        ]);

        $this->actingAs(financeManager())
            ->post("/admin/fees/{$fee->id}/waive", ['reason' => 'Founding member'])
            ->assertRedirect();

        $fee->refresh();

        expect($fee->status)->toBe(FeeStatus::Waived)
            ->and($fee->waived)->toBeTrue()
            ->and($fee->waived_reason)->toBe('Founding member');

        // The money was never received, and the accounts must not claim it was.
        expect(Payment::query()->count())->toBe(0);
    });

    it('requires a reason, because a waiver with none is an oversight', function (): void {
        $fee = MembershipFee::factory()->create(['amount' => 500]);

        $this->actingAs(financeManager())
            ->post("/admin/fees/{$fee->id}/waive", [])
            ->assertSessionHasErrors('reason');

        expect($fee->refresh()->waived)->toBeFalse();
    });
});

describe('a member reading their own money', function (): void {
    it('sees their payments and what they still owe', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);
        $member = Member::factory()->approved()->create(['user_id' => $user->id]);

        MembershipFee::factory()->create([
            'member_id' => $member->id,
            'amount' => 500,
            'status' => FeeStatus::Pending,
        ]);

        $this->actingAs($user)
            ->get('/my/payments')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('member/payments')
                ->has('outstanding', 1)
            );
    });

    it('cannot read somebody else receipt', function (): void {
        $other = Member::factory()->approved()->create();
        $fee = MembershipFee::factory()->create(['member_id' => $other->id, 'amount' => 500]);

        $payment = app(PaymentRecorder::class)
            ->recordManual($fee, financeManager());

        $user = User::factory()->create();
        $user->syncRoles(['Member']);
        Member::factory()->approved()->create(['user_id' => $user->id]);

        // 404, not 403 — an existence-revealing error is itself a disclosure.
        $this->actingAs($user)
            ->get("/my/payments/{$payment->ulid}/receipt")
            ->assertNotFound();
    });
});

describe('volunteers', function (): void {
    it('can be recorded without an alumni record', function (): void {
        // A parent, a former teacher, a sibling who turns up to help.
        $this->actingAs(volunteerCoordinator())
            ->post('/admin/volunteers', ['name' => 'Rahima Begum'])
            ->assertRedirect();

        $volunteer = Volunteer::query()->sole();

        expect($volunteer->member_id)->toBeNull()
            ->and($volunteer->name)->toBe('Rahima Begum');
    });

    it('records an assignment on the timeline of a volunteer who is a member', function (): void {
        $member = Member::factory()->approved()->create();
        $volunteer = Volunteer::factory()->create(['member_id' => $member->id]);
        $team = VolunteerTeam::factory()->create(['name' => 'Reception']);

        $this->actingAs(volunteerCoordinator())
            ->post("/admin/volunteers/{$volunteer->id}/assignments", [
                'volunteer_team_id' => $team->id,
            ])
            ->assertRedirect();

        $entry = CrmActivity::query()->where('type', CrmActivityType::System)->sole();

        expect($entry->subject_id)->toBe($member->id)
            ->and($entry->subject_line)->toContain('Reception');

        expect($volunteer->assignments()->sole()->status)->toBe(AssignmentStatus::Assigned);
    });

    it('is closed to an ordinary member', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);

        $this->actingAs($user)->get('/admin/volunteers')->assertForbidden();
    });
});

describe('committees', function (): void {
    it('keeps the record when somebody stands down', function (): void {
        $committee = Committee::factory()->create();
        $member = CommitteeMember::factory()->create([
            'committee_id' => $committee->id,
            'status' => CommitteeMemberStatus::Active,
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['Content Manager']);

        $this->actingAs($user)
            ->delete("/admin/committees/{$committee->id}/members/{$member->id}")
            ->assertRedirect();

        $member->refresh();

        // A committee's history is part of the association's record. Deleting
        // it would make the 2019 committee unreconstructable.
        expect($member->status)->toBe(CommitteeMemberStatus::Past)
            ->and($member->end_date)->not->toBeNull()
            ->and(CommitteeMember::query()->count())->toBe(1);
    });

    it('shows only serving members in public', function (): void {
        $committee = Committee::factory()->create(['status' => 'active']);

        CommitteeMember::factory()->create([
            'committee_id' => $committee->id,
            'status' => CommitteeMemberStatus::Active,
        ]);
        CommitteeMember::factory()->create([
            'committee_id' => $committee->id,
            'status' => CommitteeMemberStatus::Past,
        ]);

        $this->get('/committees')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/committees')
                ->has('committees.0.members', 1)
            );
    });
});

describe('the public donor wall', function (): void {
    it('never lists an anonymous donor', function (): void {
        Donation::factory()->create([
            'status' => DonationStatus::Received,
            'is_anonymous' => true,
            // Even with the public flag on — anonymous wins over everything.
            'is_public' => true,
            'donor_name' => 'Should Not Appear',
        ]);

        $this->get('/donate')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('recent', 0));
    });

    it('lists a received, named, public donation', function (): void {
        Donation::factory()->create([
            'status' => DonationStatus::Received,
            'is_anonymous' => false,
            'is_public' => true,
            'donor_name' => 'Abdul Karim',
        ]);

        $this->get('/donate')
            ->assertInertia(fn ($page) => $page
                ->has('recent', 1)
                ->where('recent.0.donor_name', 'Abdul Karim')
            );
    });

    it('never lists a pledge that has not been received', function (): void {
        Donation::factory()->create([
            'status' => DonationStatus::Pending,
            'is_anonymous' => false,
            'is_public' => true,
        ]);

        $this->get('/donate')
            ->assertInertia(fn ($page) => $page->has('recent', 0));
    });
});
