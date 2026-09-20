<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Models\Batch;
use App\Models\Member;
use App\Models\MentorProfile;
use App\Models\MentorshipRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('allows approved members to create and update their mentor profile', function (): void {
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    $batch = Batch::factory()->create(['ssc_year' => 2003]);
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'batch_id' => $batch->id,
        'status' => MemberStatus::Approved,
    ]);

    $this->actingAs($user)
        ->post('/mentorship/profile', [
            'title' => 'Principal Software Engineer',
            'company_or_institution' => 'Google',
            'expertise' => ['Tech Careers', 'System Design', 'Interview Prep'],
            'bio' => 'Happy to help junior alumni break into big tech.',
            'years_of_experience' => 10,
            'max_mentees' => 3,
            'is_available' => true,
        ])
        ->assertRedirect();

    $profile = MentorProfile::query()->where('member_id', $member->id)->firstOrFail();
    expect($profile->title)->toBe('Principal Software Engineer')
        ->and($profile->years_of_experience)->toBe(10)
        ->and($profile->expertise)->toContain('Tech Careers');
});

it('allows a mentee to send a mentorship request to a mentor and mentor to accept it', function (): void {
    $mentorUser = User::factory()->create();
    $mentorUser->syncRoles(['Member']);
    $mentorMember = Member::factory()->create(['user_id' => $mentorUser->id, 'status' => MemberStatus::Approved]);

    $mentorProfile = MentorProfile::factory()->create(['member_id' => $mentorMember->id]);

    $menteeUser = User::factory()->create();
    $menteeUser->syncRoles(['Member']);
    $menteeMember = Member::factory()->create(['user_id' => $menteeUser->id, 'status' => MemberStatus::Approved]);

    // Mentee sends request
    $this->actingAs($menteeUser)
        ->post('/mentorship/request', [
            'mentor_id' => $mentorMember->id,
            'topic' => 'Guidance on Masters in Germany',
            'message' => 'Hello brother, I am preparing for DAAD scholarship.',
        ])
        ->assertRedirect();

    $request = MentorshipRequest::query()->where('mentee_id', $menteeMember->id)->firstOrFail();
    expect($request->status)->toBe('pending');

    // Mentor accepts request
    $this->actingAs($mentorUser)
        ->patch("/mentorship/requests/{$request->ulid}", [
            'status' => 'accepted',
            'response_note' => 'Sure, let us connect over email this weekend.',
        ])
        ->assertRedirect();

    expect($request->fresh()->status)->toBe('accepted')
        ->and($request->fresh()->response_note)->toBe('Sure, let us connect over email this weekend.');
});

it('prevents non-approved users from accessing mentorship', function (): void {
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    $batch = Batch::factory()->create(['ssc_year' => 2010]);
    Member::factory()->create([
        'user_id' => $user->id,
        'batch_id' => $batch->id,
        'status' => MemberStatus::Pending,
    ]);

    $this->actingAs($user)
        ->get('/mentorship')
        ->assertRedirect('/my/profile'); // EnsureMemberApproved redirects pending to profile
});
