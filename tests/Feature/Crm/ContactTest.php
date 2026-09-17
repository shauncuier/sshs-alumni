<?php

declare(strict_types=1);

use App\Enums\CrmActivityType;
use App\Enums\PipelineStage;
use App\Models\CrmActivity;
use App\Models\CrmContact;
use App\Models\CrmTag;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * The CRM surface.
 *
 * @see docs/05-modules.md section 6
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function crmUser(string $role = 'CRM Manager'): User
{
    $user = User::factory()->create();
    $user->syncRoles([$role]);

    return $user;
}

describe('access', function (): void {
    it('is closed to an ordinary member', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);

        $this->actingAs($user)->get('/admin/crm/contacts')->assertForbidden();
    });

    it('lets a read-only role look but not write', function (): void {
        // Membership Manager holds crm.view but not crm.manage.
        $user = crmUser('Membership Manager');

        expect($user->can('crm.view'))->toBeTrue()
            ->and($user->can('crm.manage'))->toBeFalse();

        $this->actingAs($user)->get('/admin/crm/contacts')->assertOk();

        $this->actingAs($user)
            ->post('/admin/crm/contacts', ['type' => 'prospect', 'name' => 'Nope'])
            ->assertForbidden();
    });

    it('is deliberately not owner-scoped for reading', function (): void {
        // A contact only one person can see is a contact only one person
        // follows up.
        CrmContact::factory()->create(['owner_id' => crmUser()->id, 'name' => 'Someone Else']);

        $this->actingAs(crmUser())
            ->get('/admin/crm/contacts')
            ->assertInertia(fn ($page) => $page->has('contacts.data', 1));
    });
});

describe('creating', function (): void {
    it('belongs to whoever entered it', function (): void {
        $user = crmUser();

        $this->actingAs($user)
            ->post('/admin/crm/contacts', [
                'type' => 'prospect',
                'name' => 'Rahim Uddin',
            ])
            ->assertRedirect();

        expect(CrmContact::query()->sole()->owner_id)->toBe($user->id);
    });

    it('opens the timeline with a system entry', function (): void {
        $this->actingAs(crmUser())->post('/admin/crm/contacts', [
            'type' => 'prospect',
            'name' => 'Rahim Uddin',
        ]);

        expect(CrmActivity::query()->where('type', CrmActivityType::System)->count())
            ->toBe(1);
    });
});

describe('editing', function (): void {
    it('refuses to move the stage through the ordinary edit form', function (): void {
        $contact = CrmContact::factory()->create([
            'pipeline_status' => PipelineStage::New,
        ]);

        $this->actingAs(crmUser())
            ->put("/admin/crm/contacts/{$contact->ulid}", [
                'type' => $contact->type->value,
                'name' => $contact->name,
                'pipeline_status' => 'active',
            ])
            ->assertRedirect();

        // Stage moves go through PipelineService so they are recorded. A form
        // field would lose that.
        expect($contact->refresh()->pipeline_status)->toBe(PipelineStage::New)
            ->and(CrmActivity::query()->count())->toBe(0);
    });

    it('moves the stage through the dedicated endpoint', function (): void {
        $contact = CrmContact::factory()->create([
            'pipeline_status' => PipelineStage::New,
        ]);

        $this->actingAs(crmUser())
            ->post("/admin/crm/contacts/{$contact->ulid}/stage", ['stage' => 'interested'])
            ->assertRedirect();

        expect($contact->refresh()->pipeline_status)->toBe(PipelineStage::Interested);
    });
});

describe('linking to an alumni record', function (): void {
    it('joins the two histories', function (): void {
        $member = Member::factory()->approved()->create();
        $contact = CrmContact::factory()->create(['member_id' => null]);

        $this->actingAs(crmUser())
            ->post("/admin/crm/contacts/{$contact->ulid}/link", [
                'member_ulid' => $member->ulid,
            ])
            ->assertRedirect();

        expect($contact->refresh()->member_id)->toBe($member->id);
    });

    it('refuses a member already linked to another contact', function (): void {
        $member = Member::factory()->approved()->create();

        CrmContact::factory()->create(['member_id' => $member->id]);
        $second = CrmContact::factory()->create(['member_id' => null]);

        $this->actingAs(crmUser())
            ->post("/admin/crm/contacts/{$second->ulid}/link", [
                'member_ulid' => $member->ulid,
            ])
            ->assertRedirect();

        // One person, one record. Two contacts pointing at the same member
        // would split the very history this is meant to join.
        expect($second->refresh()->member_id)->toBeNull();
    });
});

describe('activities', function (): void {
    it('records a call', function (): void {
        $contact = CrmContact::factory()->create();

        $this->actingAs(crmUser())
            ->post("/admin/crm/contacts/{$contact->ulid}/activities", [
                'type' => 'call',
                'subject_line' => 'Rang about the reunion',
            ])
            ->assertRedirect();

        expect(CrmActivity::query()->sole()->type)->toBe(CrmActivityType::Call);
    });

    it('REFUSES to let a form fabricate a system entry', function (): void {
        $contact = CrmContact::factory()->create();

        $this->actingAs(crmUser())
            ->post("/admin/crm/contacts/{$contact->ulid}/activities", [
                'type' => 'system',
                'subject_line' => 'Identity verified',
            ])
            ->assertSessionHasErrors('type');

        // `system` rows are the platform vouching that something happened.
        // Anyone able to post one could fabricate a history.
        expect(CrmActivity::query()->count())->toBe(0);
    });

    it('refuses a postdated activity', function (): void {
        $contact = CrmContact::factory()->create();

        $this->actingAs(crmUser())
            ->post("/admin/crm/contacts/{$contact->ulid}/activities", [
                'type' => 'call',
                'occurred_at' => now()->addWeek()->toDateTimeString(),
            ])
            ->assertSessionHasErrors('occurred_at');
    });

    it('allows backdating, because a call made yesterday is real', function (): void {
        $contact = CrmContact::factory()->create();

        $this->actingAs(crmUser())
            ->post("/admin/crm/contacts/{$contact->ulid}/activities", [
                'type' => 'call',
                'occurred_at' => now()->subDay()->toDateTimeString(),
            ])
            ->assertSessionHasNoErrors();

        expect(CrmActivity::query()->sole()->occurred_at->isYesterday())->toBeTrue();
    });
});

describe('tags', function (): void {
    it('toggles on and off', function (): void {
        $contact = CrmContact::factory()->create();
        $tag = CrmTag::factory()->create();
        $user = crmUser();

        $this->actingAs($user)
            ->post("/admin/crm/contacts/{$contact->ulid}/tags", ['tag_id' => $tag->id]);

        expect($contact->refresh()->tags)->toHaveCount(1);

        $this->actingAs($user)
            ->post("/admin/crm/contacts/{$contact->ulid}/tags", ['tag_id' => $tag->id]);

        expect($contact->refresh()->tags)->toHaveCount(0);
    });

    it('refuses a duplicate name', function (): void {
        CrmTag::factory()->create(['name' => 'Donor']);

        $this->actingAs(crmUser())
            ->post('/admin/crm/tags', ['name' => 'Donor'])
            ->assertSessionHasErrors('name');
    });

    it('refuses a colour that is not a hex value', function (): void {
        // Free text here ends up in a style attribute.
        $this->actingAs(crmUser())
            ->post('/admin/crm/tags', ['name' => 'Sponsor', 'color' => 'red; background:url(x)'])
            ->assertSessionHasErrors('color');
    });
});

describe('the pipeline board', function (): void {
    it('shows every stage, including the empty ones', function (): void {
        CrmContact::factory()->create(['pipeline_status' => PipelineStage::New]);

        $this->actingAs(crmUser())
            ->get('/admin/crm/pipeline')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/crm/pipeline')
                ->has('columns', count(PipelineStage::cases()))
                ->where('columns.0.total', 1)
            );
    });
});
