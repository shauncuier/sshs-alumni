<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Enums\AudienceType;
use App\Enums\CampaignChannel;
use App\Enums\CampaignRecipientStatus;
use App\Enums\MemberStatus;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Member;
use App\Services\Communication\Sms\SmsMessage;
use Illuminate\Database\Eloquent\Builder;

class CampaignAudienceResolver
{
    /**
     * Resolve recipients and create CampaignRecipient rows for a campaign.
     *
     * @return int Number of recipients attached
     */
    public function populateRecipients(Campaign $campaign): int
    {
        // Clear any existing draft recipients if re-resolving
        $campaign->recipients()->delete();

        $members = $this->queryMembers($campaign)->get();

        $recipientsData = [];
        $now = now();

        foreach ($members as $member) {
            $email = $member->email ?? $member->user?->email;
            $phone = $member->mobile ?? $member->user?->phone;

            if ($campaign->channel === CampaignChannel::Sms) {
                if (empty($phone)) {
                    continue;
                }
                $normalizedPhone = SmsMessage::normaliseNumber($phone);
                if ($normalizedPhone === '') {
                    continue;
                }
                $phone = $normalizedPhone;
            } elseif ($campaign->channel === CampaignChannel::Mail) {
                if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
            }

            $recipientsData[] = [
                'campaign_id' => $campaign->id,
                'member_id' => $member->id,
                'crm_contact_id' => null,
                'email' => $email,
                'phone' => $phone,
                'status' => CampaignRecipientStatus::Queued->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($recipientsData)) {
            foreach (array_chunk($recipientsData, 250) as $chunk) {
                CampaignRecipient::insert($chunk);
            }
        }

        $count = count($recipientsData);
        $campaign->update(['recipients_count' => $count]);

        return $count;
    }

    /**
     * @return Builder<Member>
     */
    private function queryMembers(Campaign $campaign): Builder
    {
        $query = Member::query()->with(['batch', 'user']);
        $filters = $campaign->audience_filters ?? [];

        switch ($campaign->audience_type) {
            case AudienceType::AllMembers:
                $query->where('status', MemberStatus::Approved);
                break;

            case AudienceType::Batch:
                $batchId = $filters['batch_id'] ?? null;
                if ($batchId) {
                    $query->where('batch_id', $batchId);
                }
                $query->where('status', MemberStatus::Approved);
                break;

            case AudienceType::Status:
                $status = $filters['status'] ?? MemberStatus::Approved->value;
                $query->where('status', $status);
                break;

            case AudienceType::Role:
                $role = $filters['role'] ?? null;
                if ($role) {
                    $query->whereHas('user', fn (Builder $q) => $q->role($role));
                }
                break;

            case AudienceType::Custom:
                $memberIds = $filters['member_ids'] ?? [];
                if (! empty($memberIds)) {
                    $query->whereIn('id', $memberIds);
                }
                break;
        }

        return $query;
    }
}
