<?php

declare(strict_types=1);

namespace App\Services\Crm;

use App\Enums\CrmActivityType;
use App\Enums\PipelineStage;
use App\Models\CrmContact;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The CRM pipeline.
 *
 *   new → contacted → interested → registered → verified → active
 *                                                       ↘ inactive → archived
 *
 * Unlike membership verification, this is NOT a locked state machine. A
 * relationship is not a workflow: a donor who went quiet may be moved straight
 * back to `contacted`, and a prospect who walks in already registered skips
 * three stages. Constraining that would make the committee fight the tool.
 *
 * What IS enforced: every stage change writes a `system` activity, so the
 * timeline records who moved someone and when, even when the move itself was
 * a drag on a board.
 *
 * @see docs/05-modules.md section 6
 */
class PipelineService
{
    public function __construct(
        private readonly ActivityLogger $activities,
    ) {}

    /**
     * Move a contact to a stage, recording the move.
     *
     * Returns the contact unchanged, and writes nothing, when the stage is
     * already what was asked for — a board that fires an update on every
     * drop would fill the timeline with noise.
     */
    public function moveTo(
        CrmContact $contact,
        PipelineStage $stage,
        ?User $actor = null,
        ?string $note = null,
    ): CrmContact {
        $from = $contact->pipeline_status;

        if ($from === $stage) {
            return $contact;
        }

        return DB::transaction(function () use ($contact, $stage, $from, $actor, $note): CrmContact {
            $contact->update(['pipeline_status' => $stage]);

            $this->activities->log(
                subject: $contact,
                type: CrmActivityType::System,
                subjectLine: __('admin.crm.moved_stage', [
                    'from' => $from->label(),
                    'to' => $stage->label(),
                ]),
                body: $note,
                user: $actor,
                meta: ['from' => $from->value, 'to' => $stage->value],
            );

            return $contact->refresh();
        });
    }

    /**
     * Assign an owner, recording the handover.
     *
     * An unowned contact is nobody's job, which is how prospects go cold — so
     * the change is logged like any other.
     */
    public function assign(CrmContact $contact, ?User $owner, ?User $actor = null): CrmContact
    {
        if ($contact->owner_id === $owner?->id) {
            return $contact;
        }

        $contact->update(['owner_id' => $owner?->id]);

        $this->activities->log(
            subject: $contact,
            type: CrmActivityType::System,
            subjectLine: $owner === null
                ? __('admin.crm.unassigned')
                : __('admin.crm.assigned_to', ['name' => $owner->name]),
            user: $actor,
            meta: ['owner_id' => $owner?->id],
        );

        return $contact->refresh();
    }

    /**
     * Link a contact to the alumni record that turns out to be the same
     * person.
     *
     * THE PERSON IS NEVER DUPLICATED. After this, the contact's timeline and
     * the member's are one history — see ActivityLogger::timelineFor().
     */
    public function linkToMember(CrmContact $contact, Member $member, ?User $actor = null): CrmContact
    {
        if ($contact->member_id === $member->id) {
            return $contact;
        }

        return DB::transaction(function () use ($contact, $member, $actor): CrmContact {
            $contact->update(['member_id' => $member->id]);

            $this->activities->log(
                subject: $contact,
                type: CrmActivityType::System,
                subjectLine: __('admin.crm.linked_member', [
                    'name' => $member->full_name,
                ]),
                user: $actor,
                meta: ['member_id' => $member->id],
            );

            return $contact->refresh();
        });
    }

    /**
     * Unlink, when the match turns out to be wrong.
     *
     * The activities stay where they were written — rewriting history to
     * follow a correction would lose the record of the mistake.
     */
    public function unlinkMember(CrmContact $contact, ?User $actor = null): CrmContact
    {
        if ($contact->member_id === null) {
            return $contact;
        }

        $contact->update(['member_id' => null]);

        $this->activities->log(
            subject: $contact,
            type: CrmActivityType::System,
            subjectLine: __('admin.crm.unlinked_member'),
            user: $actor,
        );

        return $contact->refresh();
    }

    /**
     * How many contacts sit in each stage, for the board's column headings.
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        /** @var array<string, int> $counts */
        $counts = CrmContact::query()
            ->selectRaw('pipeline_status, count(*) as total')
            ->groupBy('pipeline_status')
            ->pluck('total', 'pipeline_status')
            ->all();

        $complete = [];

        foreach (PipelineStage::cases() as $stage) {
            $complete[$stage->value] = (int) ($counts[$stage->value] ?? 0);
        }

        return $complete;
    }
}
