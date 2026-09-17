<?php

declare(strict_types=1);

use App\Enums\CrmActivityType;
use App\Enums\EventStatus;
use App\Enums\MemberStatus;
use App\Enums\PipelineStage;
use App\Models\CrmActivity;
use App\Models\CrmContact;
use App\Models\Event;
use App\Models\Member;
use App\Models\User;
use App\Services\Crm\ActivityLogger;
use App\Services\Crm\PipelineService;
use App\Services\Events\EventRegistrar;
use App\Services\Membership\VerificationService;
use Database\Seeders\RolePermissionSeeder;

/**
 * THE TIMELINE.
 *
 * A person may exist as a member AND as a contact — entered as a prospect,
 * later registering, the two records linked. Their history is then split
 * across two subjects, and a timeline showing only one half would be worse
 * than no timeline: it would look complete while hiding the call that preceded
 * the registration.
 *
 * These tests prove the union, and prove that `system` rows are written by the
 * services rather than by anybody remembering to.
 *
 * @see docs/05-modules.md section 6
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function crmManager(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['CRM Manager']);

    return $user;
}

describe('one feed across two records', function (): void {
    it('merges a linked contact and member into one ordered history', function (): void {
        $logger = app(ActivityLogger::class);

        $member = Member::factory()->approved()->create();
        $contact = CrmContact::factory()->create(['member_id' => $member->id]);

        $logger->log(
            $contact,
            CrmActivityType::Call,
            'Called about the reunion',
            occurredAt: now()->subDays(3),
        );

        $logger->system($member, 'Identity verified');

        $timeline = $logger->timelineFor($contact);

        // Both halves, newest first.
        expect($timeline)->toHaveCount(2)
            ->and($timeline->first()->subject_line)->toBe('Identity verified')
            ->and($timeline->last()->subject_line)->toBe('Called about the reunion');
    });

    it('reads the same history from either end', function (): void {
        $logger = app(ActivityLogger::class);

        $member = Member::factory()->approved()->create();
        $contact = CrmContact::factory()->create(['member_id' => $member->id]);

        $logger->log($contact, CrmActivityType::Note, 'On the contact');
        $logger->log($member, CrmActivityType::Note, 'On the member');

        expect($logger->timelineFor($contact))->toHaveCount(2)
            ->and($logger->timelineFor($member))->toHaveCount(2);
    });

    it('does not bleed between unlinked people', function (): void {
        $logger = app(ActivityLogger::class);

        $member = Member::factory()->approved()->create();
        $contact = CrmContact::factory()->create(['member_id' => null]);

        $logger->log($member, CrmActivityType::Note, 'Member only');

        expect($logger->timelineFor($contact))->toHaveCount(0);
    });

    it('keeps the activities where they were written when a link is undone', function (): void {
        $logger = app(ActivityLogger::class);

        $member = Member::factory()->approved()->create();
        $contact = CrmContact::factory()->create(['member_id' => $member->id]);

        $logger->log($member, CrmActivityType::Note, 'On the member');

        app(PipelineService::class)->unlinkMember($contact, crmManager());

        // Rewriting history to follow a correction would lose the record of
        // the mistake.
        expect(CrmActivity::query()->count())->toBe(2)
            ->and($logger->timelineFor($contact->refresh()))
            // The unlink note is on the contact; the member note is not.
            ->toHaveCount(1);
    });
});

describe('system entries write themselves', function (): void {
    it('records a membership verification', function (): void {
        $member = Member::factory()->create(['status' => MemberStatus::Pending]);

        app(VerificationService::class)->transition(
            $member,
            MemberStatus::Approved,
            crmManager(),
        );

        $entry = CrmActivity::query()
            ->where('type', CrmActivityType::System)
            ->sole();

        expect($entry->subject_id)->toBe($member->id)
            ->and($entry->subject_line)->toContain('Approved')
            ->and($entry->meta['to'])->toBe('approved');
    });

    it('records an event registration', function (): void {
        $member = Member::factory()->approved()->create();

        $event = Event::factory()->create([
            'status' => EventStatus::RegistrationOpen,
            'registration_required' => true,
            'capacity' => null,
            'title' => 'Golden Jubilee 2026',
        ]);

        app(EventRegistrar::class)->registerMember($event, $member);

        $entry = CrmActivity::query()
            ->where('type', CrmActivityType::System)
            ->sole();

        expect($entry->subject_line)->toContain('Golden Jubilee 2026')
            ->and($entry->meta['event_id'])->toBe($event->id);
    });

    it('does not fall over for a walk-in with no record at all', function (): void {
        $event = Event::factory()->create([
            'status' => EventStatus::RegistrationOpen,
            'registration_required' => true,
        ]);

        // A walk-in has neither a member nor a contact. That is a real case,
        // not an error.
        app(EventRegistrar::class)->registerGuest(
            $event,
            ['name' => 'Abdul Karim'],
        );

        expect(CrmActivity::query()->count())->toBe(0);
    });

    it('records a pipeline move with who did it', function (): void {
        $contact = CrmContact::factory()->create([
            'pipeline_status' => PipelineStage::New,
        ]);

        $actor = crmManager();

        app(PipelineService::class)->moveTo(
            $contact,
            PipelineStage::Interested,
            $actor,
        );

        $entry = CrmActivity::query()->where('type', CrmActivityType::System)->sole();

        expect($entry->user_id)->toBe($actor->id)
            ->and($entry->meta['from'])->toBe('new')
            ->and($entry->meta['to'])->toBe('interested');
    });

    it('writes nothing when a move is a no-op', function (): void {
        $contact = CrmContact::factory()->create([
            'pipeline_status' => PipelineStage::Interested,
        ]);

        app(PipelineService::class)->moveTo(
            $contact,
            PipelineStage::Interested,
            crmManager(),
        );

        // A board that fires an update on every drop would fill the timeline
        // with noise.
        expect(CrmActivity::query()->count())->toBe(0);
    });
});

describe('last_activity_at', function (): void {
    it('is maintained by the logger rather than by every caller', function (): void {
        $contact = CrmContact::factory()->create(['last_activity_at' => null]);

        app(ActivityLogger::class)->log($contact, CrmActivityType::Call, 'Rang');

        expect($contact->refresh()->last_activity_at)->not->toBeNull();
    });

    it('moves when the activity was written on the linked member', function (): void {
        $member = Member::factory()->approved()->create();
        $contact = CrmContact::factory()->create([
            'member_id' => $member->id,
            'last_activity_at' => null,
        ]);

        app(ActivityLogger::class)->log($member, CrmActivityType::Call, 'Rang');

        // The contact list sorts by this, and a call is a call whichever
        // record it was filed against.
        expect($contact->refresh()->last_activity_at)->not->toBeNull();
    });
});
