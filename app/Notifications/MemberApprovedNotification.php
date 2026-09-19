<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Member;

class MemberApprovedNotification extends AppNotification
{
    public function __construct(Member $member)
    {
        $membershipNo = $member->membership_no ?? '';

        parent::__construct(
            title: 'Membership Approved!',
            body: "Congratulations! Your alumni membership has been verified and approved. Your Membership No is {$membershipNo}.",
            actionUrl: route('my.card'),
            actionText: 'View Digital Card',
            category: 'membership',
            icon: 'CheckCircle2',
        );
    }
}
