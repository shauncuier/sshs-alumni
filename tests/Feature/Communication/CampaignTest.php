<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Enums\AudienceType;
use App\Enums\CampaignChannel;
use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Enums\MemberStatus;
use App\Jobs\SendCampaignBatch;
use App\Models\Batch;
use App\Models\Campaign;
use App\Models\Member;
use App\Models\User;
use App\Services\Communication\SmsManager;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function communicationManager(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['CRM Manager']); // CRM Manager has campaigns.manage

    return $user;
}

function superAdmin(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Super Admin']); // Has all permissions including campaigns.send

    return $user;
}

describe('campaign drafting and audience resolution', function (): void {
    it('creates a draft campaign and resolves approved members', function (): void {
        $batch = Batch::factory()->create(['ssc_year' => 2005]);

        // 2 approved members with valid mobile
        Member::factory()->approved()->for($batch)->create(['mobile' => '01712345678']);
        Member::factory()->approved()->for($batch)->create(['mobile' => '01812345678']);
        // 1 pending member (should be excluded)
        Member::factory()->for($batch)->create(['mobile' => '01912345678', 'status' => MemberStatus::Pending]);

        $manager = communicationManager();

        $response = $this->actingAs($manager)->post('/admin/campaigns', [
            'name' => 'Batch 2005 Reunion Alert',
            'channel' => CampaignChannel::Sms->value,
            'body' => 'Hello alumni, join us for the reunion!',
            'audience_type' => AudienceType::Batch->value,
            'audience_filters' => ['batch_id' => $batch->id],
        ]);

        $response->assertRedirect();

        $campaign = Campaign::query()->where('name', 'Batch 2005 Reunion Alert')->first();
        expect($campaign)->not->toBeNull()
            ->and($campaign->status)->toBe(CampaignStatus::Draft)
            ->and($campaign->recipients_count)->toBe(2);

        expect($campaign->recipients()->count())->toBe(2);
        expect($campaign->recipients()->first()->status)->toBe(CampaignRecipientStatus::Queued);
    });

    it('estimates Unicode Bangla segments and cost higher than GSM-7 Latin', function (): void {
        $manager = app(SmsManager::class);

        $latinText = 'This is a test notification message for the SSHS alumni association.';
        $banglaText = 'এটি সবুজ শিক্ষায়তন প্রাক্তন ছাত্র-ছাত্রী পরিষদের একটি বার্তা।';

        $latinEstimate = $manager->estimate($latinText, 100);
        $banglaEstimate = $manager->estimate($banglaText, 100);

        expect($latinEstimate['is_unicode'])->toBeFalse();
        expect($latinEstimate['segments'])->toBe(1);

        expect($banglaEstimate['is_unicode'])->toBeTrue();
        expect($banglaEstimate['segments'])->toBeGreaterThanOrEqual(1);
    });
});

describe('campaign permissions and dispatching', function (): void {
    it('prevents a manager with only campaigns.manage from dispatching without campaigns.send', function (): void {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::Draft,
            'channel' => CampaignChannel::Sms,
        ]);

        $manager = communicationManager(); // Has campaigns.manage, lacks campaigns.send

        $this->actingAs($manager)
            ->post("/admin/campaigns/{$campaign->id}/send")
            ->assertForbidden();
    });

    it('dispatches queued jobs when sent by an authorized administrator', function (): void {
        Queue::fake();

        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::Draft,
            'channel' => CampaignChannel::Sms,
            'body' => 'Important alumni notification',
        ]);

        $member = Member::factory()->approved()->create(['mobile' => '01711223344']);
        $campaign->recipients()->create([
            'member_id' => $member->id,
            'phone' => '8801711223344',
            'status' => CampaignRecipientStatus::Queued,
        ]);
        $campaign->update(['recipients_count' => 1]);

        $admin = superAdmin();

        $this->actingAs($admin)
            ->post("/admin/campaigns/{$campaign->id}/send")
            ->assertRedirect();

        Queue::assertPushed(SendCampaignBatch::class);

        expect($campaign->fresh()->status)->toBe(CampaignStatus::Sending);
    });

    it('processes SendCampaignBatch and updates recipient status to sent in log mode', function (): void {
        config(['sms.driver' => 'log', 'sms.enabled' => true]);

        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::Sending,
            'channel' => CampaignChannel::Sms,
            'body' => 'Welcome alumni!',
        ]);

        $member = Member::factory()->approved()->create(['mobile' => '01711223344']);
        $recipient = $campaign->recipients()->create([
            'member_id' => $member->id,
            'phone' => '8801711223344',
            'status' => CampaignRecipientStatus::Queued,
        ]);

        $job = new SendCampaignBatch($campaign->id, [$recipient->id]);
        $job->handle(app(SmsManager::class));

        $recipient->refresh();
        $campaign->refresh();

        expect($recipient->status)->toBe(CampaignRecipientStatus::Sent)
            ->and($recipient->sent_at)->not->toBeNull()
            ->and($campaign->sent_count)->toBe(1)
            ->and($campaign->status)->toBe(CampaignStatus::Completed);
    });
});
