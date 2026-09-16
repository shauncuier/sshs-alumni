<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberPrivacy;
use App\Services\Membership\ProfileCompletionCalculator;
use Illuminate\Support\Str;

/**
 * Side effects that must never be forgotten when a member changes:
 * the search blob, the profile-completion score and the batch counter cache.
 *
 * These live in an observer rather than a service because they have to happen
 * on EVERY write — including seeders, imports and admin edits — not only on
 * the paths someone remembered to call a service from.
 *
 * @see docs/01-architecture.md section 7
 */
class MemberObserver
{
    public function __construct(
        private readonly ProfileCompletionCalculator $completion,
    ) {}

    public function creating(Member $member): void
    {
        $member->search_blob = $this->searchBlob($member);
        $member->profile_completion = $this->completion->calculate($member);

        if ($member->registered_at === null) {
            $member->registered_at = now();
        }
    }

    public function created(Member $member): void
    {
        // Every member has privacy settings. Creating the row here means no
        // code path can produce a member without them, so the directory never
        // has to cope with a missing privacy relation.
        if ($member->privacy()->doesntExist()) {
            MemberPrivacy::query()->create(['member_id' => $member->id]);
        }

        $this->recount($member->batch_id);
    }

    public function updating(Member $member): void
    {
        if ($this->touchesSearchableFields($member)) {
            $member->search_blob = $this->searchBlob($member);
        }

        $member->profile_completion = $this->completion->calculate($member);
    }

    public function updated(Member $member): void
    {
        if ($member->wasChanged('batch_id')) {
            $this->recount($member->getOriginal('batch_id'));
            $this->recount($member->batch_id);

            return;
        }

        // Approving or suspending a member changes who counts as a batch
        // member, so the cached total has to move with it.
        if ($member->wasChanged('status')) {
            $this->recount($member->batch_id);
        }
    }

    public function deleted(Member $member): void
    {
        $this->recount($member->batch_id);
    }

    public function restored(Member $member): void
    {
        $this->recount($member->batch_id);
    }

    /**
     * Lowercased name, Bangla name, organization, occupation and city in one
     * indexed column — one LIKE instead of five OR-ed column scans.
     */
    private function searchBlob(Member $member): string
    {
        $parts = array_filter([
            $member->full_name,
            $member->full_name_bn,
            $member->organization,
            $member->occupation,
            $member->city,
            $member->district,
            $member->membership_no,
        ]);

        return Str::limit(Str::lower(implode(' ', $parts)), 490, '');
    }

    private function touchesSearchableFields(Member $member): bool
    {
        return $member->isDirty([
            'full_name', 'full_name_bn', 'organization',
            'occupation', 'city', 'district', 'membership_no',
        ]);
    }

    /**
     * Recalculate a batch's cached member count.
     *
     * Only approved members count — a pending application is not yet part of
     * the batch as far as the public page is concerned.
     */
    private function recount(mixed $batchId): void
    {
        if (blank($batchId)) {
            return;
        }

        Batch::query()->whereKey($batchId)->update([
            'members_count' => Member::query()
                ->where('batch_id', $batchId)
                ->approved()
                ->count(),
        ]);
    }
}
