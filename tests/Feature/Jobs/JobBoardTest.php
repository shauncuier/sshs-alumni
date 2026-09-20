<?php

declare(strict_types=1);

use App\Enums\ContentStatus;
use App\Enums\MemberStatus;
use App\Models\Batch;
use App\Models\JobPosting;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('allows approved members to view jobs', function (): void {
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    $batch = Batch::factory()->create(['ssc_year' => 2002]);
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'batch_id' => $batch->id,
        'status' => MemberStatus::Approved,
    ]);

    $job = JobPosting::factory()->create([
        'member_id' => $member->id,
        'title' => 'Senior Laravel Engineer',
        'company_name' => 'Acme Corp',
        'workplace_type' => 'remote',
        'employment_type' => 'full_time',
        'status' => ContentStatus::Published,
    ]);

    $this->actingAs($user)
        ->get('/jobs')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('jobs.data', 1)
            ->where('jobs.data.0.title', 'Senior Laravel Engineer'));

    $this->actingAs($user)
        ->get("/jobs/{$job->ulid}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('job.company_name', 'Acme Corp'));
});

it('allows approved members to post and manage job opportunities', function (): void {
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    $batch = Batch::factory()->create(['ssc_year' => 2002]);
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'batch_id' => $batch->id,
        'status' => MemberStatus::Approved,
    ]);

    // Create job
    $this->actingAs($user)
        ->post('/jobs', [
            'title' => 'Product Designer',
            'company_name' => 'Design Studio BD',
            'location' => 'Dhaka',
            'workplace_type' => 'hybrid',
            'employment_type' => 'full_time',
            'experience_level' => 'mid',
            'salary_range' => 'BDT 70k-100k',
            'description' => 'Looking for talented alumnus designer.',
            'application_url_or_email' => 'jobs@designstudio.test',
        ])
        ->assertRedirect();

    $job = JobPosting::query()->where('title', 'Product Designer')->firstOrFail();
    expect($job->member_id)->toBe($member->id);

    // Update job
    $this->actingAs($user)
        ->put("/jobs/{$job->ulid}", [
            'title' => 'Lead Product Designer',
            'company_name' => 'Design Studio BD',
            'workplace_type' => 'hybrid',
            'employment_type' => 'full_time',
            'description' => 'Updated job description.',
            'application_url_or_email' => 'jobs@designstudio.test',
        ])
        ->assertRedirect();

    expect($job->refresh()->title)->toBe('Lead Product Designer');

    // Delete job
    $this->actingAs($user)
        ->delete("/jobs/{$job->ulid}")
        ->assertRedirect();

    expect($job->fresh()->trashed())->toBeTrue();
});

it('prevents non-approved users from posting jobs', function (): void {
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    $batch = Batch::factory()->create(['ssc_year' => 2002]);
    Member::factory()->create([
        'user_id' => $user->id,
        'batch_id' => $batch->id,
        'status' => MemberStatus::Pending,
    ]);

    $this->actingAs($user)
        ->post('/jobs', [
            'title' => 'Unauthorized Job',
            'company_name' => 'Hacker Co',
            'workplace_type' => 'remote',
            'employment_type' => 'full_time',
            'description' => 'Test',
            'application_url_or_email' => 'apply@test.com',
        ])
        ->assertRedirect('/my/profile'); // EnsureMemberApproved redirects pending to profile
});
