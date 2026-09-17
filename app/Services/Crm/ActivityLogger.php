<?php

declare(strict_types=1);

namespace App\Services\Crm;

use App\Enums\CrmActivityType;
use App\Models\CrmActivity;
use App\Models\CrmContact;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * The activity timeline.
 *
 * One polymorphic table over members AND contacts, which is what lets a member
 * have a history without a shadow contact row.
 *
 * The important part is `timelineFor()`. A person may exist as a member AND as
 * a contact — someone is entered as a prospect, later registers, and the two
 * records are linked. Their history is then split across two subjects, and a
 * timeline that showed only one half would be worse than no timeline: it would
 * look complete while hiding the call that preceded the registration.
 *
 * So the timeline reads BOTH subjects and orders the union.
 *
 * @see docs/05-modules.md section 6
 */
class ActivityLogger
{
    /**
     * Record something a person did.
     *
     * @param  array<string, mixed>|null  $meta
     */
    public function log(
        Model $subject,
        CrmActivityType $type,
        ?string $subjectLine = null,
        ?string $body = null,
        ?string $outcome = null,
        ?User $user = null,
        ?array $meta = null,
        ?\DateTimeInterface $occurredAt = null,
    ): CrmActivity {
        // Falls back to the signed-in user, so a caller inside a request does
        // not have to thread it through. A queued job has neither, and the row
        // then honestly records "no person did this".
        $actorId = $user !== null ? $user->id : Auth::id();

        $activity = CrmActivity::query()->create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'type' => $type,
            'subject_line' => $subjectLine,
            'body' => $body,
            'outcome' => $outcome,
            'user_id' => $actorId,
            'meta' => $meta,
            'occurred_at' => $occurredAt ?? now(),
        ]);

        $this->touchContact($subject);

        return $activity;
    }

    /**
     * Record something the SYSTEM did — a verification, a registration, a
     * payment.
     *
     * Separate from `log()` because these carry no acting user by default and
     * because they are what make the timeline complete without anybody
     * remembering to write a note.
     *
     * @param  array<string, mixed>|null  $meta
     */
    public function system(
        Model $subject,
        string $subjectLine,
        ?string $body = null,
        ?array $meta = null,
    ): CrmActivity {
        return $this->log(
            subject: $subject,
            type: CrmActivityType::System,
            subjectLine: $subjectLine,
            body: $body,
            meta: $meta,
        );
    }

    /**
     * The whole history of a person, however their records are split.
     *
     * @return Collection<int, CrmActivity>
     */
    public function timelineFor(Model $subject, int $limit = 100): Collection
    {
        $subjects = $this->linkedSubjects($subject);

        $query = CrmActivity::query()->with('user');

        $query->where(function ($outer) use ($subjects): void {
            foreach ($subjects as [$type, $id]) {
                $outer->orWhere(function ($inner) use ($type, $id): void {
                    $inner->where('subject_type', $type)
                        ->where('subject_id', $id);
                });
            }
        });

        return $query
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Every (morph type, id) pair that belongs to the same person.
     *
     * A Member and the CrmContact linked to them are one person with two rows.
     *
     * @return array<int, array{0: string, 1: int}>
     */
    private function linkedSubjects(Model $subject): array
    {
        $pairs = [[$subject->getMorphClass(), (int) $subject->getKey()]];

        if ($subject instanceof Member) {
            $contact = $subject->crmContact()->first();

            if ($contact !== null) {
                $pairs[] = [$contact->getMorphClass(), $contact->id];
            }
        }

        if ($subject instanceof CrmContact && $subject->member_id !== null) {
            $pairs[] = [(new Member)->getMorphClass(), $subject->member_id];
        }

        return $pairs;
    }

    /**
     * `last_activity_at` is what the contact list sorts and filters by, so it
     * is maintained here rather than by every caller.
     */
    private function touchContact(Model $subject): void
    {
        $contact = match (true) {
            $subject instanceof CrmContact => $subject,
            $subject instanceof Member => $subject->crmContact()->first(),
            default => null,
        };

        $contact?->forceFill(['last_activity_at' => now()])->save();
    }
}
