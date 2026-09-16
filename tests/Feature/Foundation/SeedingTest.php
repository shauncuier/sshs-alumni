<?php

declare(strict_types=1);

use App\Enums\EventDateStatus;
use App\Enums\MemberStatus;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Event;
use App\Models\Member;
use App\Models\Page;
use App\Models\SchoolMilestone;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ProductionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

describe('roles and permissions', function (): void {
    beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

    it('creates every role', function (): void {
        expect(Role::query()->count())->toBe(11);
    });

    it('gives Super Admin every permission', function (): void {
        expect(Role::findByName('Super Admin')->permissions()->count())
            ->toBe(Permission::query()->count());
    });

    it('gives an ordinary Member no administrative access', function (): void {
        $member = Role::findByName('Member');

        expect($member->hasPermissionTo('admin.access'))->toBeFalse()
            ->and($member->hasPermissionTo('members.verify'))->toBeFalse()
            ->and($member->hasPermissionTo('payments.refund'))->toBeFalse();
    });

    it('lets a Volunteer Coordinator check in without editing the event', function (): void {
        // They staff and run the gate; they do not change the event.
        $role = Role::findByName('Volunteer Coordinator');

        expect($role->hasPermissionTo('events.checkin'))->toBeTrue()
            ->and($role->hasPermissionTo('events.edit'))->toBeFalse();
    });

    it('does not let a Moderator touch money', function (): void {
        $role = Role::findByName('Moderator');

        expect($role->hasPermissionTo('community.moderate'))->toBeTrue()
            ->and($role->hasPermissionTo('payments.view'))->toBeFalse();
    });

    it('is idempotent', function (): void {
        $before = Permission::query()->count();

        $this->seed(RolePermissionSeeder::class);

        expect(Permission::query()->count())->toBe($before)
            ->and(Role::query()->count())->toBe(11);
    });

    it('bypasses every gate for Super Admin', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Super Admin']);

        expect($user->can('anything.at.all'))->toBeTrue();
    });

    it('does not bypass gates for anyone else', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(['Member']);

        expect($user->can('members.verify'))->toBeFalse();
    });
});

describe('settings', function (): void {
    beforeEach(fn () => $this->seed(SettingsSeeder::class));

    it('keeps the association and the school as separate founding years', function (): void {
        // The fifty years belong to the SCHOOL. The association organises.
        // Conflating these misstates the history of both bodies.
        expect(setting('school.established'))->toBe(1976)
            ->and(setting('organization.established'))->toBe(2015);
    });

    it('records the verified school details', function (): void {
        expect(setting('school.eiin'))->toBe('105070')
            ->and(setting('school.name_en'))->toBe('Sabuj Shikshayatan Government High School');
    });

    it('never leaks a non-public setting to the frontend', function (): void {
        setting()->set('system.secret_thing', 'hidden', isPublic: false);
        setting()->set('system.shown_thing', 'visible', isPublic: true);

        $public = setting()->publicSettings();

        expect($public['system'] ?? [])->toHaveKey('shown_thing')
            ->and($public['system'] ?? [])->not->toHaveKey('secret_thing');
    });

    it('does not overwrite an administrator edit when re-seeded', function (): void {
        setting()->set('organization.name_en', 'Edited By Committee', isPublic: true);

        $this->seed(SettingsSeeder::class);

        expect(setting('organization.name_en'))->toBe('Edited By Committee');
    });

    it('invalidates its cache on write', function (): void {
        setting()->set('system.cache_probe', 'first');
        expect(setting('system.cache_probe'))->toBe('first');

        setting()->set('system.cache_probe', 'second');
        expect(setting('system.cache_probe'))->toBe('second');
    });

    it('rejects a path that is not group.key', function (): void {
        expect(fn () => setting()->set('nogroup', 'x'))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('the Golden Jubilee seed', function (): void {
    beforeEach(fn () => $this->seed(ProductionSeeder::class));

    it('seeds the Jubilee with NO date set', function (): void {
        $jubilee = Event::query()->where('is_flagship', true)->firstOrFail();

        expect($jubilee->date_status)->toBe(EventDateStatus::Tba)
            ->and($jubilee->starts_at)->toBeNull()
            ->and($jubilee->dateIsTba())->toBeTrue();
    });

    it('has no hard-coded Jubilee date anywhere in the seeders', function (): void {
        // Guards against a well-meaning future edit pinning a date in code
        // instead of publishing it from the admin panel.
        $seeders = File::files(database_path('seeders'));
        $offenders = [];

        foreach ($seeders as $file) {
            $contents = File::get($file->getPathname());

            if (preg_match('/20(2[5-9]|3\d)-\d{2}-\d{2}/', $contents) === 1) {
                $offenders[] = $file->getFilename();
            }
        }

        expect($offenders)->toBe([]);
    });

    it('seeds both founding years on the timeline', function (): void {
        // Showing only 1976 would imply the association is fifty years old.
        expect(SchoolMilestone::query()->pluck('year')->sort()->values()->all())
            ->toBe([1976, 2015]);
    });

    it('creates batches from the first SSC cohort onward', function (): void {
        expect(Batch::query()->min('ssc_year'))->toBe(1981)
            ->and(Batch::query()->where('ssc_year', 1980)->exists())->toBeFalse();
    });

    it('marks the privacy policy and terms as undeletable system pages', function (): void {
        expect(Page::query()->where('slug', 'privacy-policy')->value('is_system'))->toBeTrue()
            ->and(Page::query()->where('slug', 'terms')->value('is_system'))->toBeTrue();
    });

    it('seeds no demo data', function (): void {
        expect(User::query()->where('email', 'like', '%@example.test')->exists())->toBeFalse();
    });

    it('is safe to run twice', function (): void {
        $batches = Batch::query()->count();
        $settings = Setting::query()->count();

        $this->seed(ProductionSeeder::class);

        expect(Batch::query()->count())->toBe($batches)
            ->and(Setting::query()->count())->toBe($settings)
            ->and(Event::query()->where('is_flagship', true)->count())->toBe(1);
    });
});

describe('demo data safety', function (): void {
    it('refuses to run in production', function (): void {
        // Demo accounts have a published password. They must never exist on a
        // live deployment, so the seeder guards itself rather than relying on
        // whoever runs it.
        $this->app['env'] = 'production';

        // Invoked directly rather than through $this->seed(), so the guard
        // itself is what is under test rather than the console kernel.
        expect(fn () => (new DemoSeeder)->run())
            ->toThrow(RuntimeException::class);
    });

    it('marks every demo member so it can be purged', function (): void {
        $this->seed(ProductionSeeder::class);
        $this->seed(DemoSeeder::class);

        $demoMembers = Member::query()->where('full_name', 'like', '%'.DemoSeeder::MARKER.'%')->count();

        expect($demoMembers)->toBeGreaterThan(0)
            ->and(Member::query()->count())->toBe($demoMembers);
    });

    it('purges demo accounts completely', function (): void {
        $this->seed(ProductionSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->artisan('demo:purge', ['--force' => true])->assertSuccessful();

        expect(User::withTrashed()->where('email', 'like', '%@example.test')->exists())->toBeFalse()
            ->and(Member::withTrashed()->where('full_name', 'like', '%'.DemoSeeder::MARKER.'%')->exists())->toBeFalse();
    });
});

describe('member observers', function (): void {
    beforeEach(fn () => $this->seed(ProductionSeeder::class));

    it('creates the privacy row for every member', function (): void {
        // No code path may produce a member without privacy settings, or the
        // directory would have to cope with a missing relation.
        $member = Member::factory()->create();

        expect($member->privacy()->exists())->toBeTrue()
            ->and($member->privacy->show_phone)->toBeFalse()
            ->and($member->privacy->show_email)->toBeFalse();
    });

    it('builds the search blob on create and keeps it current', function (): void {
        $member = Member::factory()->create(['full_name' => 'Rahim Uddin']);

        expect($member->search_blob)->toContain('rahim uddin');

        $member->update(['full_name' => 'Karim Uddin']);

        expect($member->refresh()->search_blob)->toContain('karim uddin');
    });

    it('scores profile completion', function (): void {
        $sparse = Member::factory()->create([
            'photo_path' => null, 'bio' => null, 'occupation' => null,
            'organization' => null, 'city' => null, 'district' => null,
        ]);

        $full = Member::factory()->create([
            'photo_path' => 'x.jpg', 'bio' => 'A bio', 'occupation' => 'Engineer',
            'organization' => 'Acme', 'city' => 'Sitakunda', 'district' => 'Chattogram',
        ]);

        expect($full->profile_completion)->toBeGreaterThan($sparse->profile_completion);
    });

    it('keeps the batch counter accurate as members move and change status', function (): void {
        $batch = Batch::query()->where('ssc_year', 1995)->firstOrFail();

        Member::factory()->count(3)->create([
            'batch_id' => $batch->id,
            'status' => MemberStatus::Approved,
        ]);

        expect($batch->refresh()->members_count)->toBe(3);

        // A pending member is not yet part of the batch publicly.
        Member::factory()->create([
            'batch_id' => $batch->id,
            'status' => MemberStatus::Pending,
        ]);

        expect($batch->refresh()->members_count)->toBe(3);
    });
});

describe('audit trail', function (): void {
    it('records a change without recording secrets', function (): void {
        $user = User::factory()->create();
        $user->update(['name' => 'Renamed']);

        $log = AuditLog::query()
            ->where('auditable_type', $user->getMorphClass())
            ->where('auditable_id', $user->id)
            ->latest('id')
            ->first();

        expect($log)->not->toBeNull()
            ->and($log->after)->toHaveKey('name')
            ->and($log->after)->not->toHaveKey('password')
            ->and($log->after)->not->toHaveKey('remember_token');
    });
});
