<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PaymentGatewayName: string
{
    use HasLabel;

    case Manual = 'manual';
}
