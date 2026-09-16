<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum RelationType: string
{
    use HasLabel;

    case FormerStudent = 'former_student';
    case FormerTeacher = 'former_teacher';
    case CurrentTeacher = 'current_teacher';
    case Staff = 'staff';
    case Guardian = 'guardian';
    case Supporter = 'supporter';
    case Other = 'other';
}
