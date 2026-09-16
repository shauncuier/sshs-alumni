<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum CampaignRecipientStatus: string
{
    use HasLabel;

    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Opened = 'opened';
    case Bounced = 'bounced';
    case Unsubscribed = 'unsubscribed';
}
