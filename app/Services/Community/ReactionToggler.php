<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Enums\ReactionType;
use App\Models\Comment;
use App\Models\Member;
use App\Models\Post;
use App\Models\Reaction;
use Illuminate\Database\QueryException;

/**
 * One reaction per member per item — enforced by the database, not by an `if`.
 *
 * Two taps on a phone with a bad connection arrive as two requests. A check-
 * then-insert loses that race and writes two rows; the UNIQUE index cannot.
 * So the insert is attempted and the unique violation is caught and treated as
 * "already reacted", which is exactly what it means.
 *
 * This is the same pattern as event check-in, for the same reason.
 *
 * @see App\Services\Events\CheckinService
 */
class ReactionToggler
{
    /**
     * Add, change or remove this member's reaction.
     *
     * Tapping the reaction you already gave removes it. Tapping a different
     * one changes it. Neither creates a second row.
     *
     * @return ReactionType|null The reaction now standing, or null if removed.
     */
    public function toggle(Post|Comment $item, Member $member, ReactionType $type): ?ReactionType
    {
        $existing = Reaction::query()
            ->where('reactable_type', $item->getMorphClass())
            ->where('reactable_id', $item->getKey())
            ->where('member_id', $member->id)
            ->first();

        if ($existing !== null) {
            if ($existing->type === $type) {
                $existing->delete();

                return null;
            }

            $existing->update(['type' => $type]);

            return $type;
        }

        try {
            Reaction::query()->create([
                'reactable_type' => $item->getMorphClass(),
                'reactable_id' => $item->getKey(),
                'member_id' => $member->id,
                'type' => $type,
            ]);
        } catch (QueryException $exception) {
            if (! $this->isUniqueViolation($exception)) {
                throw $exception;
            }

            // Lost the race. The other request wrote the row; that is the
            // outcome this one wanted.
            return $type;
        }

        return $type;
    }

    /**
     * MySQL reports a duplicate key as 1062; SQLite as 19 with UNIQUE in the
     * message. Both drivers have to be read, because the suite runs on SQLite
     * and production runs on MySQL.
     */
    private function isUniqueViolation(QueryException $exception): bool
    {
        $code = $exception->errorInfo[1] ?? null;

        if ($code === 1062) {
            return true;
        }

        return $code === 19
            && str_contains(strtoupper($exception->getMessage()), 'UNIQUE');
    }
}
