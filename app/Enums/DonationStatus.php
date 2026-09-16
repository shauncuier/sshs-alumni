<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum DonationStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Received = 'received';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
