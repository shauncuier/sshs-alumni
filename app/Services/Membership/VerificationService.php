<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\MemberVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The membership verification state machine.
 *
 * Every transition is validated against the allowed map and recorded in
 * `member_verifications`, so the history is complete by construction rather
 * than because each caller remembered to log.
 *
 *   pending ──> under_review ──> approved
 *      │             │      └──> rejected
 *      │             └────────> (correction requested; stays under_review)
 *      └──> archived
 *
 *   approved ──> suspended ──> approved | archived
 *
 * @see docs/05-modules.md section 2
 */
class VerificationService
{
    /**
     * Which statuses each status may move to.
     *
     * @var array<string, array<int, MemberStatus>>
     */
    private const TRANSITIONS = [
        'pending' => [
            MemberStatus::UnderReview,
            MemberStatus::Approved,
            MemberStatus::Rejected,
            MemberStatus::Archived,
        ],
        'under_review' => [
            MemberStatus::Approved,
            MemberStatus::Rejected,
            MemberStatus::Pending,
            MemberStatus::Archived,
        ],
        'approved' => [
            MemberStatus::Suspended,
            MemberStatus::Archived,
        ],
        'rejected' => [
            MemberStatus::UnderReview,
            MemberStatus::Archived,
        ],
        'suspended' => [
            MemberStatus::Approved,
            MemberStatus::Archived,
        ],
        'archived' => [
            MemberStatus::UnderReview,
        ],
    ];

    public function __construct(
        private readonly MembershipNumberGenerator $numbers,
    ) {}

    public function canTransition(MemberStatus $from, MemberStatus $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from->value], true);
    }

    /**
     * @return array<int, MemberStatus>
     */
    public function allowedFrom(MemberStatus $from): array
    {
        return self::TRANSITIONS[$from->value];
    }

    /**
     * Move a member to a new status, recording who did it and why.
     *
     * @param  string|null  $note  Internal. Never shown to the member.
     */
    public function transition(
        Member $member,
        MemberStatus $to,
        User $actor,
        ?string $note = null,
    ): Member {
        $from = $member->status;

        if (! $this->canTransition($from, $to)) {
            throw new InvalidArgumentException(
                "A member cannot move from {$from->value} to {$to->value}."
            );
        }

        return DB::transaction(function () use ($member, $from, $to, $actor, $note): Member {
            $attributes = ['status' => $to];

            if ($to === MemberStatus::Approved) {
                $attributes['verified_at'] = now();
                $attributes['verified_by'] = $actor->id;

                // Issued once and kept. A member who is suspended and later
                // restored keeps the number they were given.
                if (blank($member->membership_no)) {
                    $attributes['membership_no'] = $this->numbers->generate($member);
                }
            }

            $member->forceFill($attributes)->save();

            $this->record($member, $from, $to, $actor, $note);

            return $member->refresh();
        });
    }

    /**
     * Ask the applicant to correct something.
     *
     * The status becomes `under_review` rather than moving forward — the
     * application stays open, and the message is recorded separately from the
     * committee's internal note because the member will read it.
     */
    public function requestCorrection(
        Member $member,
        User $actor,
        string $message,
        ?string $note = null,
    ): Member {
        $from = $member->status;

        return DB::transaction(function () use ($member, $from, $actor, $message, $note): Member {
            if ($from !== MemberStatus::UnderReview) {
                $member->forceFill(['status' => MemberStatus::UnderReview])->save();
            }

            MemberVerification::query()->create([
                'member_id' => $member->id,
                'actor_id' => $actor->id,
                'from_status' => $from,
                'to_status' => MemberStatus::UnderReview,
                'note' => $note,
                'correction_requested' => $message,
                'created_at' => now(),
            ]);

            return $member->refresh();
        });
    }

    /**
     * Set or override a membership number by hand.
     *
     * Audited like any other administrative action, because overriding a
     * generated identifier is exactly the kind of thing a committee later
     * needs to explain.
     */
    public function assignNumber(Member $member, User $actor, ?string $number = null): Member
    {
        $number ??= $this->numbers->generate($member);

        $member->forceFill(['membership_no' => $number])->save();

        $this->record(
            $member,
            $member->status,
            $member->status,
            $actor,
            "Membership number set to {$number}.",
        );

        return $member->refresh();
    }

    private function record(
        Member $member,
        MemberStatus $from,
        MemberStatus $to,
        User $actor,
        ?string $note,
    ): void {
        MemberVerification::query()->create([
            'member_id' => $member->id,
            'actor_id' => $actor->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
