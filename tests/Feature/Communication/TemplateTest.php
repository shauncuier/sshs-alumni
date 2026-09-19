<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Enums\CampaignChannel;
use App\Models\Batch;
use App\Models\Member;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\Communication\TemplateRenderer;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

describe('message templates', function (): void {
    it('creates a new message template', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['CRM Manager']);

        $this->actingAs($user)->post('/admin/message-templates', [
            'name' => 'Event Invitation Template',
            'channel' => CampaignChannel::Sms->value,
            'body' => 'Dear {name}, please attend our upcoming event.',
        ])->assertRedirect();

        $template = MessageTemplate::query()->where('name', 'Event Invitation Template')->first();
        expect($template)->not->toBeNull()
            ->and($template->channel)->toBe(CampaignChannel::Sms);
    });

    it('blocks deletion of a system template', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['CRM Manager']);

        $systemTemplate = MessageTemplate::factory()->create([
            'is_system' => true,
            'name' => 'System Verification Notice',
        ]);

        $this->actingAs($user)
            ->delete("/admin/message-templates/{$systemTemplate->id}")
            ->assertForbidden();

        expect($systemTemplate->fresh())->not->toBeNull();
    });

    it('interpolates template tags for a member correctly', function (): void {
        $batch = Batch::factory()->create(['ssc_year' => 2002]);
        $member = Member::factory()->approved()->for($batch)->create([
            'full_name' => 'Rafiqul Islam',
            'membership_no' => 'SSHS-2002-0045',
        ]);

        $variables = TemplateRenderer::extractVariables($member);
        $rendered = TemplateRenderer::render(
            'Hello {name}, your batch is {batch} and membership ID is {membership_no}.',
            $variables
        );

        expect($rendered)->toBe('Hello Rafiqul Islam, your batch is 2002 and membership ID is SSHS-2002-0045.');
    });
});
