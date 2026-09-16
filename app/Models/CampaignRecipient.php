<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignRecipientStatus;
use Carbon\CarbonImmutable;
use Database\Factories\CampaignRecipientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One recipient's delivery state.
 *
 * `error` holds provider error text only. API keys are never written here.
 *
 * @property int $id
 * @property int $campaign_id
 * @property int|null $member_id
 * @property int|null $crm_contact_id
 * @property string|null $email
 * @property string|null $phone
 * @property CampaignRecipientStatus $status
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $delivered_at
 * @property CarbonImmutable|null $opened_at
 * @property string|null $error
 * @property string|null $provider_message_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'campaign_id', 'member_id', 'crm_contact_id', 'email', 'phone', 'status', 'sent_at',
    'delivered_at', 'opened_at', 'error', 'provider_message_id',
])]
class CampaignRecipient extends Model
{
    /** @use HasFactory<CampaignRecipientFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CampaignRecipientStatus::class,
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'opened_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<CrmContact, $this>
     */
    public function crmContact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class);
    }
}
