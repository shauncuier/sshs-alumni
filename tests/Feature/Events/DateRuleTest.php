<?php

declare(strict_types=1);

use App\Enums\EventDateStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\JubileeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * THE DATE RULE.
 *
 * The committee has not fixed a Golden Jubilee date. Until an administrator
 * publishes one, every public surface says so and no countdown exists.
 *
 * These tests check the SERVER side of that promise — that `starts_at` is not
 * merely hidden by a component but ABSENT from the payload. A component cannot
 * leak a date it was never given, which is the whole reason the rule is
 * enforced in the resource rather than in JSX.
 *
 * @see docs/17-golden-jubilee.md section 2
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
});

function publisher(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Event Manager']);

    return $user;
}

describe('no date literal exists', function (): void {
    it('seeds the Jubilee with no date at all', function (): void {
        $this->seed(JubileeSeeder::class);

        $jubilee = Event::query()->where('is_flagship', true)->sole();

        expect($jubilee->starts_at)->toBeNull()
            ->and($jubilee->ends_at)->toBeNull()
            ->and($jubilee->date_status)->toBe(EventDateStatus::Tba)
            ->and($jubilee->dateIsTba())->toBeTrue();
    });

    it('carries no Jubilee date in any config or seeder', function (): void {
        // A date literal anywhere would defeat every other test here.
        $files = array_merge(
            glob(base_path('config/*.php')) ?: [],
            glob(database_path('seeders/*.php')) ?: [],
        );

        $offenders = [];

        foreach ($files as $file) {
            $contents = (string) file_get_contents($file);

            // 2026-MM-DD in any form.
            if (preg_match('/2026[-\/]\d{2}[-\/]\d{2}/', $contents) === 1) {
                $offenders[] = basename($file);
            }
        }

        expect($offenders)->toBe([]);
    });
});

describe('the public payload', function (): void {
    it('omits starts_at entirely while the date is unannounced', function (): void {
        $event = Event::factory()->create([
            'status' => EventStatus::Published,
            'date_status' => EventDateStatus::Tba,
            'starts_at' => now()->addYear(),
        ]);

        $this->get("/events/{$event->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('event.date_status', 'tba')
                // ABSENT, not null. A null would still tell a component the
                // key exists, and a careless `?? ''` would render something.
                ->missing('event.starts_at')
                ->missing('event.ends_at')
            );
    });

    it('includes the date once it is announced', function (): void {
        $event = Event::factory()->create([
            'status' => EventStatus::Published,
            'date_status' => EventDateStatus::Announced,
            'starts_at' => now()->addYear(),
        ]);

        $this->get("/events/{$event->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('event.date_status', 'announced')
                ->has('event.starts_at')
            );
    });

    it('omits it on the Jubilee landing page too', function (): void {
        $this->seed(JubileeSeeder::class);

        $this->get('/jubilee')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('jubilee/index')
                ->where('event.date_status', 'tba')
                ->missing('event.starts_at')
            );
    });

    it('treats a null starts_at as TBA even if the status says otherwise', function (): void {
        // Belt and braces: `dateIsTba()` checks both, so a row that somehow
        // says `announced` with no date still renders the TBA line.
        $event = Event::factory()->create([
            'status' => EventStatus::Published,
            'date_status' => EventDateStatus::Announced,
            'starts_at' => null,
        ]);

        expect($event->dateIsTba())->toBeTrue();

        $this->get("/events/{$event->slug}")
            ->assertInertia(fn ($page) => $page->missing('event.starts_at'));
    });
});

describe('announcing', function (): void {
    it('does not announce a date merely because the edit form posted one', function (): void {
        $event = Event::factory()->create([
            'status' => EventStatus::Published,
            'date_status' => EventDateStatus::Tba,
            'starts_at' => null,
        ]);

        $this->actingAs(publisher())
            ->put("/admin/events/{$event->ulid}", [
                'type' => $event->type->value,
                'title' => $event->title,
                'starts_at' => now()->addMonths(3)->toDateTimeString(),
                'registration_required' => false,
                'currency' => 'BDT',
                // Trying to announce through the ordinary edit endpoint.
                'date_status' => 'announced',
                'status' => 'registration_open',
            ])
            ->assertRedirect();

        $event->refresh();

        expect($event->starts_at)->not->toBeNull()
            // The draft date is saved; announcing it is a separate act.
            ->and($event->date_status)->toBe(EventDateStatus::Tba);
    });

    it('announces through the dedicated endpoint', function (): void {
        $event = Event::factory()->create([
            'date_status' => EventDateStatus::Tba,
            'starts_at' => null,
        ]);

        $this->actingAs(publisher())
            ->post("/admin/events/{$event->ulid}/date", [
                'announce' => true,
                'starts_at' => now()->addMonths(3)->toDateTimeString(),
            ])
            ->assertRedirect();

        expect($event->refresh()->date_status)->toBe(EventDateStatus::Announced);
    });

    it('refuses to announce without a date', function (): void {
        $event = Event::factory()->create(['date_status' => EventDateStatus::Tba]);

        $this->actingAs(publisher())
            ->post("/admin/events/{$event->ulid}/date", ['announce' => true])
            ->assertSessionHasErrors('starts_at');

        expect($event->refresh()->date_status)->toBe(EventDateStatus::Tba);
    });

    it('can be retracted, because plans change', function (): void {
        $event = Event::factory()->create([
            'date_status' => EventDateStatus::Announced,
            'starts_at' => now()->addMonths(3),
        ]);

        $this->actingAs(publisher())
            ->post("/admin/events/{$event->ulid}/date", ['announce' => false])
            ->assertRedirect();

        expect($event->refresh()->date_status)->toBe(EventDateStatus::Tba);
    });

    it('is closed to someone without events.publish', function (): void {
        $event = Event::factory()->create(['date_status' => EventDateStatus::Tba]);

        $user = User::factory()->create();
        // Content Manager holds admin.access but not events.publish.
        $user->syncRoles(['Content Manager']);

        $this->actingAs($user)
            ->post("/admin/events/{$event->ulid}/date", [
                'announce' => true,
                'starts_at' => now()->addMonths(3)->toDateTimeString(),
            ])
            ->assertForbidden();

        expect($event->refresh()->date_status)->toBe(EventDateStatus::Tba);
    });
});
