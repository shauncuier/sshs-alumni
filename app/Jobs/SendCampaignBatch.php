<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CampaignChannel;
use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Mail\CampaignEmail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Services\Communication\SmsManager;
use App\Services\Communication\TemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendCampaignBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, int>  $recipientIds
     */
    public function __construct(
        public int $campaignId,
        public array $recipientIds,
    ) {}

    public function handle(SmsManager $smsManager): void
    {
        $campaign = Campaign::find($this->campaignId);
        if ($campaign === null || $campaign->status === CampaignStatus::Cancelled) {
            return;
        }

        if ($campaign->status === CampaignStatus::Draft || $campaign->status === CampaignStatus::Scheduled) {
            $campaign->update([
                'status' => CampaignStatus::Sending,
                'started_at' => $campaign->started_at ?? now(),
            ]);
        }

        $recipients = CampaignRecipient::query()
            ->whereIn('id', $this->recipientIds)
            ->where('status', CampaignRecipientStatus::Queued)
            ->with(['member.user', 'crmContact'])
            ->get();

        foreach ($recipients as $recipient) {
            $notifiable = $recipient->member ?? $recipient->crmContact;
            $user = $recipient->member?->user;
            $locale = $user?->locale ?? 'en';

            $variables = TemplateRenderer::extractVariables($recipient->member ?? $recipient->crmContact);
            $rawBody = TemplateRenderer::resolveBody($campaign, $locale);
            $rawSubject = TemplateRenderer::resolveSubject($campaign, $locale);

            $renderedBody = TemplateRenderer::render($rawBody, $variables);
            $renderedSubject = $rawSubject ? TemplateRenderer::render($rawSubject, $variables) : null;

            if ($campaign->channel === CampaignChannel::Sms) {
                $this->sendSms($campaign, $recipient, $renderedBody, $smsManager);
            } elseif ($campaign->channel === CampaignChannel::Mail) {
                $this->sendMail($campaign, $recipient, $renderedBody, $renderedSubject);
            }
        }

        // If no more recipients queued across the campaign, mark completed
        $remaining = CampaignRecipient::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', CampaignRecipientStatus::Queued)
            ->count();

        if ($remaining === 0) {
            $campaign->update([
                'status' => CampaignStatus::Completed,
                'completed_at' => now(),
            ]);
        }
    }

    private function sendSms(Campaign $campaign, CampaignRecipient $recipient, string $body, SmsManager $smsManager): void
    {
        if (empty($recipient->phone)) {
            $recipient->update([
                'status' => CampaignRecipientStatus::Failed,
                'error' => 'Missing phone number.',
            ]);
            $campaign->increment('failed_count');

            return;
        }

        try {
            $result = $smsManager->send($recipient->phone, $body);

            if ($result->ok) {
                $recipient->update([
                    'status' => CampaignRecipientStatus::Sent,
                    'sent_at' => now(),
                    'provider_message_id' => $result->providerMessageId,
                ]);
                $campaign->increment('sent_count');
            } else {
                $recipient->update([
                    'status' => CampaignRecipientStatus::Failed,
                    'error' => $result->message,
                ]);
                $campaign->increment('failed_count');
            }
        } catch (Throwable $e) {
            Log::error('SMS campaign send error: '.$e->getMessage());
            $recipient->update([
                'status' => CampaignRecipientStatus::Failed,
                'error' => 'Transport exception: '.$e->getMessage(),
            ]);
            $campaign->increment('failed_count');
        }
    }

    private function sendMail(Campaign $campaign, CampaignRecipient $recipient, string $body, ?string $subject): void
    {
        if (empty($recipient->email)) {
            $recipient->update([
                'status' => CampaignRecipientStatus::Failed,
                'error' => 'Missing email address.',
            ]);
            $campaign->increment('failed_count');

            return;
        }

        try {
            Mail::to($recipient->email)->send(
                new CampaignEmail($campaign, $recipient, $body, $subject)
            );

            $recipient->update([
                'status' => CampaignRecipientStatus::Sent,
                'sent_at' => now(),
            ]);
            $campaign->increment('sent_count');
        } catch (Throwable $e) {
            Log::error('Mail campaign send error: '.$e->getMessage());
            $recipient->update([
                'status' => CampaignRecipientStatus::Failed,
                'error' => 'Mail exception: '.$e->getMessage(),
            ]);
            $campaign->increment('failed_count');
        }
    }
}
