<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\Member;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Issues membership numbers in the form SSHS-{SSC_YEAR}-{SEQ}.
 *
 * The sequence is scoped per batch, so SSHS-1990-0001 is the first member of
 * SSC 1990 rather than the first member overall — which is what a committee
 * reading a list actually wants.
 *
 * Generated inside a transaction with a row lock: two administrators approving
 * at the same moment must not be handed the same number, and `membership_no`
 * is UNIQUE so a collision would surface as an error in front of one of them.
 *
 * Numbers stay in Latin digits in both languages so they can be quoted over
 * the phone, searched and typed reliably.
 *
 * @see docs/05-modules.md section 2
 */
class MembershipNumberGenerator
{
    public function generate(Member $member): string
    {
        $prefix = (string) (setting('membership.number_prefix') ?? 'SSHS');

        // Members with no SSC year — teachers, staff, guardians — are grouped
        // under their relation rather than forced into a batch they never
        // belonged to.
        $scope = $member->ssc_year !== null
            ? (string) $member->ssc_year
            : strtoupper(substr($member->relation_type->value, 0, 4));

        return DB::transaction(function () use ($member, $prefix, $scope): string {
            $pattern = "{$prefix}-{$scope}-%";

            // lockForUpdate serialises concurrent approvals within this scope.
            $highest = Member::query()
                ->where('membership_no', 'like', $pattern)
                ->whereKeyNot($member->getKey())
                ->lockForUpdate()
                ->orderByDesc('membership_no')
                ->value('membership_no');

            $next = $highest === null
                ? 1
                : ((int) substr((string) $highest, strrpos((string) $highest, '-') + 1)) + 1;

            if ($next > 9999) {
                throw new RuntimeException(
                    "Membership numbers for {$prefix}-{$scope} are exhausted."
                );
            }

            return sprintf('%s-%s-%04d', $prefix, $scope, $next);
        });
    }
}
