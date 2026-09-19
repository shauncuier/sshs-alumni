<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AudienceType;
use App\Enums\CampaignChannel;
use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendCampaignBatch;
use App\Models\Batch;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\MessageTemplate;
use App\Services\Communication\CampaignAudienceResolver;
use App\Services\Communication\SmsManager;
use App\Support\Paginated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class CampaignController extends Controller
{
    public function __construct(
        private readonly SmsManager $smsManager,
        private readonly CampaignAudienceResolver $audienceResolver,
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $channel = $request->query('channel');

        $campaigns = Campaign::query()
            ->with(['creator', 'template'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($channel, fn ($q) => $q->where('channel', $channel))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/campaigns/index', [
            'campaigns' => Paginated::from($campaigns, fn (Campaign $c): array => [
                'id' => $c->id,
                'name' => $c->name,
                'channel' => $c->channel->value,
                'subject' => $c->subject,
                'subject_bn' => $c->subject_bn,
                'body' => $c->body,
                'body_bn' => $c->body_bn,
                'audience_type' => $c->audience_type->value,
                'audience_filters' => $c->audience_filters,
                'scheduled_at' => $c->scheduled_at?->toIso8601String(),
                'started_at' => $c->started_at?->toIso8601String(),
                'completed_at' => $c->completed_at?->toIso8601String(),
                'status' => $c->status->value,
                'recipients_count' => $c->recipients_count,
                'sent_count' => $c->sent_count,
                'failed_count' => $c->failed_count,
                'segments_per_message' => $c->segments_per_message,
                'estimated_cost' => $c->estimated_cost,
                'created_at' => $c->created_at?->toIso8601String() ?? now()->toIso8601String(),
                'creator' => $c->creator ? ['id' => $c->creator->id, 'name' => $c->creator->name] : null,
                'template' => $c->template ? ['id' => $c->template->id, 'name' => $c->template->name] : null,
            ]),
            'filters' => [
                'status' => $status,
                'channel' => $channel,
            ],
            'smsMetrics' => [
                'balance' => $this->smsManager->balance(),
                'enabled' => $this->smsManager->isEnabled(),
                'sentToday' => $this->smsManager->sentToday(),
                'remainingToday' => $this->smsManager->remainingToday(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/campaigns/create', [
            'channels' => CampaignChannel::cases(),
            'audiences' => AudienceType::cases(),
            'batches' => Batch::query()->orderBy('ssc_year')->get(['id', 'ssc_year']),
            'roles' => Role::query()->pluck('name'),
            'templates' => MessageTemplate::query()->get(['id', 'key', 'name', 'channel', 'subject', 'body', 'body_bn', 'variables']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'channel' => ['required', Rule::enum(CampaignChannel::class)],
            'subject' => ['nullable', 'string', 'max:255', Rule::requiredIf($request->channel === CampaignChannel::Mail->value)],
            'subject_bn' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'body_bn' => ['nullable', 'string'],
            'audience_type' => ['required', Rule::enum(AudienceType::class)],
            'audience_filters' => ['nullable', 'array'],
            'message_template_id' => ['nullable', 'exists:message_templates,id'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $campaign = new Campaign;
        $campaign->fill($validated);
        $campaign->created_by = $request->user()?->id;
        $campaign->status = CampaignStatus::Draft;

        if ($campaign->channel === CampaignChannel::Sms) {
            $estimate = $this->smsManager->estimate($campaign->body, 1);
            $campaign->segments_per_message = $estimate['segments'];
        }

        $campaign->save();

        // Populate initial recipients based on audience
        $count = $this->audienceResolver->populateRecipients($campaign);

        // Recalculate cost if SMS
        if ($campaign->channel === CampaignChannel::Sms) {
            $estimate = $this->smsManager->estimate($campaign->body, $count);
            // Rough benchmark cost in BDT: ~0.35 BDT per SMS segment
            $campaign->estimated_cost = (string) ($estimate['total_segments'] * 0.35);
            $campaign->recipients_count = $count;
            $campaign->save();
        }

        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('success', 'Campaign draft created successfully.');
    }

    public function show(Campaign $campaign, Request $request): Response
    {
        $campaign->load(['creator', 'template']);

        $status = $request->query('status');

        $recipients = CampaignRecipient::query()
            ->where('campaign_id', $campaign->id)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['member.batch'])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/campaigns/show', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'channel' => $campaign->channel->value,
                'subject' => $campaign->subject,
                'subject_bn' => $campaign->subject_bn,
                'body' => $campaign->body,
                'body_bn' => $campaign->body_bn,
                'audience_type' => $campaign->audience_type->value,
                'audience_filters' => $campaign->audience_filters,
                'scheduled_at' => $campaign->scheduled_at?->toIso8601String(),
                'started_at' => $campaign->started_at?->toIso8601String(),
                'completed_at' => $campaign->completed_at?->toIso8601String(),
                'status' => $campaign->status->value,
                'recipients_count' => $campaign->recipients_count,
                'sent_count' => $campaign->sent_count,
                'failed_count' => $campaign->failed_count,
                'segments_per_message' => $campaign->segments_per_message,
                'estimated_cost' => $campaign->estimated_cost,
                'created_at' => $campaign->created_at?->toIso8601String() ?? now()->toIso8601String(),
                'creator' => $campaign->creator ? ['id' => $campaign->creator->id, 'name' => $campaign->creator->name] : null,
                'template' => $campaign->template ? ['id' => $campaign->template->id, 'name' => $campaign->template->name] : null,
            ],
            'recipients' => Paginated::from($recipients, fn (CampaignRecipient $r): array => [
                'id' => $r->id,
                'campaign_id' => $r->campaign_id,
                'email' => $r->email,
                'phone' => $r->phone,
                'status' => $r->status->value,
                'sent_at' => $r->sent_at?->toIso8601String(),
                'delivered_at' => $r->delivered_at?->toIso8601String(),
                'error' => $r->error,
                'member' => $r->member ? [
                    'id' => $r->member->id,
                    'full_name' => $r->member->full_name,
                    'batch' => $r->member->batch ? ['ssc_year' => $r->member->batch->ssc_year] : null,
                ] : null,
            ]),
            'filters' => [
                'status' => $status,
            ],
            'estimate' => $campaign->channel === CampaignChannel::Sms
                ? $this->smsManager->estimate($campaign->body, $campaign->recipients_count)
                : null,
        ]);
    }

    public function send(Campaign $campaign, Request $request): RedirectResponse
    {
        if ($campaign->status === CampaignStatus::Sending || $campaign->status === CampaignStatus::Completed) {
            return back()->with('error', 'Campaign is already being processed or has completed.');
        }

        if ($campaign->recipients()->count() === 0) {
            $this->audienceResolver->populateRecipients($campaign);
        }

        $recipientIds = $campaign->recipients()
            ->where('status', CampaignRecipientStatus::Queued)
            ->pluck('id')
            ->all();

        if (empty($recipientIds)) {
            return back()->with('error', 'No eligible recipients found for this campaign.');
        }

        $campaign->update([
            'status' => CampaignStatus::Sending,
            'started_at' => now(),
        ]);

        // Dispatch in chunks of 100
        foreach (array_chunk($recipientIds, 100) as $chunk) {
            SendCampaignBatch::dispatch($campaign->id, $chunk);
        }

        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('success', 'Campaign dispatch started. Recipient queues are processing.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        if ($campaign->status === CampaignStatus::Sending) {
            return back()->with('error', 'Cannot delete an active campaign.');
        }

        $campaign->delete();

        return redirect()->route('admin.campaigns.index')
            ->with('success', 'Campaign deleted.');
    }
}
