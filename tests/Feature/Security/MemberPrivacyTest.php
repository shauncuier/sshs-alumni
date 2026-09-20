<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * THE PRIVACY TESTS.
 *
 * A member told the platform what the world may see. If these fail, the
 * platform broke that promise — which is worse than a crash, because nobody
 * notices.
 *
 * Each asserts the field is ABSENT from the payload, not merely blank. A
 * present-but-empty key still tells you the field exists, and assertDontSee
 * alone would pass against a value that never had a chance to render.
 *
 * @see docs/08-security-privacy.md section 12
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * An approved member who may use the directory.
 */
function directoryViewer(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    Member::factory()->create([
        'user_id' => $user->id,
        'status' => MemberStatus::Approved,
    ]);

    return $user;
}

/**
 * A directory-visible member carrying known-secret values.
 *
 * @param  array<string, bool>  $privacy
 * @param  array<string, mixed>  $attributes
 */
function memberWithPrivacy(array $privacy = [], array $attributes = []): Member
{
    $batch = Batch::query()->first() ?? Batch::factory()->create(['ssc_year' => 1999]);

    $member = Member::factory()->create([
        'status' => MemberStatus::Approved,
        'batch_id' => $batch->id,
        'full_name' => 'Zzz Subject',
        'mobile' => '01712345678',
        'whatsapp' => '01712345679',
        'email' => 'private@example.test',
        'occupation' => 'Secret Occupation',
        'organization' => 'Secret Organisation',
        'city' => 'Secretville',
        'district' => 'Secret District',
        'address' => '12 Secret Lane',
        'emergency_contact_phone' => '01999999999',
        'student_id' => 'STU-SECRET',
        ...$attributes,
    ]);

    $member->privacy()->update([
        'show_profile' => true,
        'show_phone' => false,
        'show_email' => false,
        'show_workplace' => false,
        'show_location' => false,
        'show_date_of_birth' => false,
        'show_in_batch_list' => true,
        ...$privacy,
    ]);

    return $member->refresh();
}

/**
 * The directory narrowed to the test subject.
 *
 * The viewer is a directory member as well, so `members.data.0` is whichever
 * name sorts first — not necessarily the member under test. Searching pins it.
 */
function directoryShowing(mixed $test, string $name = 'Zzz Subject')
{
    return $test->actingAs(directoryViewer())->get('/directory?q='.urlencode($name));
}

describe('the directory listing', function (): void {
    it('omits the phone number when the member hid it', function (): void {
        memberWithPrivacy(['show_phone' => false]);

        directoryShowing($this)
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->missing('members.data.0.mobile')
                ->missing('members.data.0.whatsapp'))
            ->assertDontSee('01712345678');
    });

    it('includes the phone number when the member shared it', function (): void {
        memberWithPrivacy(['show_phone' => true]);

        directoryShowing($this)
            ->assertInertia(fn ($page) => $page
                ->where('members.data.0.mobile', '01712345678'));
    });

    it('omits the email when the member hid it', function (): void {
        memberWithPrivacy(['show_email' => false]);

        directoryShowing($this)
            ->assertInertia(fn ($page) => $page->missing('members.data.0.email'))
            ->assertDontSee('private@example.test');
    });

    it('omits the workplace when the member hid it', function (): void {
        memberWithPrivacy(['show_workplace' => false]);

        directoryShowing($this)
            ->assertInertia(fn ($page) => $page
                ->missing('members.data.0.occupation')
                ->missing('members.data.0.organization'))
            ->assertDontSee('Secret Organisation');
    });

    it('omits the location when the member hid it', function (): void {
        memberWithPrivacy(['show_location' => false]);

        directoryShowing($this)
            ->assertInertia(fn ($page) => $page
                ->missing('members.data.0.city')
                ->missing('members.data.0.district'))
            ->assertDontSee('Secretville');
    });

    it('omits blood group by default when the member has not consented', function (): void {
        memberWithPrivacy(['show_blood_group' => false], ['blood_group' => 'A+']);

        directoryShowing($this)
            ->assertInertia(fn ($page) => $page->missing('members.data.0.blood_group'));
    });

    it('includes blood group when the member has explicitly consented', function (): void {
        memberWithPrivacy(['show_blood_group' => true], ['blood_group' => 'A+']);

        directoryShowing($this)
            ->assertInertia(fn ($page) => $page->where('members.data.0.blood_group', 'A+'));
    });

    it('never exposes administrative fields, whatever the flags say', function (): void {
        // Address, emergency contact and student id exist for the
        // association administrative use. No privacy flag opens them to
        // other members.
        memberWithPrivacy([
            'show_phone' => true,
            'show_email' => true,
            'show_workplace' => true,
            'show_location' => true,
            'show_date_of_birth' => true,
        ]);

        directoryShowing($this)
            ->assertDontSee('12 Secret Lane')
            ->assertDontSee('01999999999')
            ->assertDontSee('STU-SECRET')
            ->assertInertia(fn ($page) => $page
                ->missing('members.data.0.address')
                ->missing('members.data.0.emergency_contact_phone')
                ->missing('members.data.0.student_id')
                ->missing('members.data.0.date_of_birth'));
    });

    it('excludes a member who hid their profile entirely', function (): void {
        memberWithPrivacy(['show_profile' => false], ['full_name' => 'Hidden Person']);

        // Asserting on the result set rather than the HTML: the search term
        // itself is echoed back in the page props, so assertDontSee would
        // match the filter rather than a leak.
        directoryShowing($this, 'Hidden Person')
            ->assertInertia(fn ($page) => $page->has('members.data', 0));
    });

    it('excludes members who are not approved', function (): void {
        Member::factory()->create([
            'status' => MemberStatus::Pending,
            'full_name' => 'Not Yet Approved',
        ]);

        directoryShowing($this, 'Not Yet Approved')
            ->assertInertia(fn ($page) => $page->has('members.data', 0));
    });
});

describe('a directory profile', function (): void {
    it('applies the same rules as the listing', function (): void {
        $member = memberWithPrivacy(['show_phone' => false, 'show_email' => false]);

        $this->actingAs(directoryViewer())
            ->get("/directory/{$member->ulid}")
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->missing('member.mobile')
                ->missing('member.email'));
    });

    it('404s for a member who hid their profile', function (): void {
        // Not a 403 — an existence-revealing error is itself a disclosure.
        $member = memberWithPrivacy(['show_profile' => false]);

        $this->actingAs(directoryViewer())
            ->get("/directory/{$member->ulid}")
            ->assertNotFound();
    });

    it('404s for a member who is not approved', function (): void {
        $member = Member::factory()->create(['status' => MemberStatus::Pending]);

        $this->actingAs(directoryViewer())
            ->get("/directory/{$member->ulid}")
            ->assertNotFound();
    });
});

describe('directory access', function (): void {
    it('is closed to guests', function (): void {
        $this->get('/directory')->assertRedirect(route('login'));
    });

    it('is closed to a member whose application is still pending', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);
        Member::factory()->create([
            'user_id' => $user->id,
            'status' => MemberStatus::Pending,
        ]);

        $this->actingAs($user)->get('/directory')->assertRedirect(route('my.profile'));
    });

    it('is closed to a suspended member', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);
        Member::factory()->create([
            'user_id' => $user->id,
            'status' => MemberStatus::Suspended,
        ]);

        $this->actingAs($user)->get('/directory')->assertRedirect(route('my.profile'));
    });
});

describe('the public QR verification page', function (): void {
    it('exposes only the fields needed to confirm a card', function (): void {
        // Every flag ON. The verification page must still not widen.
        $member = memberWithPrivacy([
            'show_phone' => true,
            'show_email' => true,
            'show_workplace' => true,
            'show_location' => true,
            'show_date_of_birth' => true,
        ]);

        $this->get("/verify/member/{$member->ulid}")
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->has('member.full_name')
                ->has('member.membership_no')
                ->has('member.is_verified')
                ->missing('member.mobile')
                ->missing('member.email')
                ->missing('member.occupation')
                ->missing('member.organization')
                ->missing('member.city')
                ->missing('member.address')
                ->missing('member.date_of_birth'))
            ->assertDontSee('01712345678')
            ->assertDontSee('private@example.test')
            ->assertDontSee('Secret Organisation');
    });

    it('is reachable without signing in', function (): void {
        $member = memberWithPrivacy();

        $this->get("/verify/member/{$member->ulid}")->assertSuccessful();
    });

    it('answers plainly for an unknown card rather than erroring', function (): void {
        $this->get('/verify/member/01JUNKJUNKJUNKJUNKJUNKJUNK')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->where('member', null));
    });
});

describe('public batch pages', function (): void {
    it('show counts but never member names', function (): void {
        $member = memberWithPrivacy([], ['full_name' => 'Visible Alumnus']);

        $this->get('/batches')
            ->assertSuccessful()
            ->assertDontSee('Visible Alumnus');

        $this->get("/batches/{$member->batch->slug}")
            ->assertSuccessful()
            ->assertDontSee('Visible Alumnus');
    });
});
