<?php

declare(strict_types=1);

use App\Enums\AudienceType;
use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Outbound communication. Channel-agnostic: the same campaign shape drives
 * email through Laravel Mail and SMS through BulkSMSBD.
 *
 * Open tracking is recorded only when the mail provider supports it — the
 * schema does not assume it.
 *
 * @see docs/05-modules.md section 13
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('message_template_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->string('channel', 16);
            $table->string('subject', 255)->nullable();
            $table->string('subject_bn', 255)->nullable();
            $table->longText('body');
            $table->longText('body_bn')->nullable();
            $table->string('audience_type', 24)->default(AudienceType::AllMembers->value);
            $table->json('audience_filters')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status', 16)->default(CampaignStatus::Draft->value);
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('opened_count')->default(0);
            // For SMS: Bangla sends as Unicode at 70 characters per segment
            // against 160 for Latin, so cost is estimated before sending.
            $table->unsignedSmallInteger('segments_per_message')->default(1);
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('channel', 'campaigns_channel_index');
            $table->index('status', 'campaigns_status_index');
            $table->index('scheduled_at', 'campaigns_scheduled_at_index');
        });

        Schema::create('campaign_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('crm_contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email', 191)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('status', 16)->default(CampaignRecipientStatus::Queued->value);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            // Provider error text only. API keys are never written here.
            $table->text('error')->nullable();
            $table->string('provider_message_id', 191)->nullable();
            $table->timestamps();

            $table->index('provider_message_id', 'campaign_recipients_provider_id_index');
            $table->index(['campaign_id', 'status'], 'campaign_recipients_campaign_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaigns');
    }
};
