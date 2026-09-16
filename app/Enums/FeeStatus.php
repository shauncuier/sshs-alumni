<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum FeeStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Paid = 'paid';
    case Waived = 'waived';
    case Cancelled = 'cancelled';
}
