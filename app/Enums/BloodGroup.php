<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum BloodGroup: string
{
    use HasLabel;

    case APositive = 'A+';
    case ANegative = 'A-';
    case BPositive = 'B+';
    case BNegative = 'B-';
    case AbPositive = 'AB+';
    case AbNegative = 'AB-';
    case OPositive = 'O+';
    case ONegative = 'O-';
}
