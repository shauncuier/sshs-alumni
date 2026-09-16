<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum CampaignChannel: string
{
    use HasLabel;

    case Mail = 'mail';
    case Sms = 'sms';
    case Whatsapp = 'whatsapp';
    case Database = 'database';
}
