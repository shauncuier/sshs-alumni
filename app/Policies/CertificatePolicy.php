<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->member !== null;
    }

    public function view(User $user, Certificate $certificate): bool
    {
        return $user->member !== null && $user->member->id === $certificate->member_id;
    }
}
