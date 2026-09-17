<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Support\Translations;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\File;

/**
 * The platform reads in English.
 *
 * What Bangla remains is the organisation's and the school's own names and the
 * Golden Jubilee's title and theme line. Those are identity, not translation,
 * and they live in `settings` as ordinary values — so the tests below check
 * that the TRANSLATION machinery is gone while those settings survive.
 *
 * @see docs/06-localization.md
 */
describe('one language', function (): void {
    it('has no Bangla language directory', function (): void {
        expect(File::isDirectory(lang_path('bn')))->toBeFalse();
    });

    it('has no locale switch route', function (): void {
        expect(Route::has('locale.switch'))->toBeFalse();
    });

    it('resolves English', function (): void {
        $this->get('/')->assertOk();

        expect(app()->getLocale())->toBe('en');
    });

    it('does not ship a locale or a locale list to the frontend', function (): void {
        $this->get('/')->assertInertia(fn ($page) => $page
            ->has('translations')
            ->missing('locale')
            ->missing('locales')
        );
    });

    it('creates a user without a language preference', function (): void {
        // The column is gone; building a User must not reference it.
        $user = new User(['name' => 'Test', 'email' => 'x@example.test']);
        $user->password = 'secret';
        $user->save();

        $this->actingAs($user)->get('/')->assertOk();

        expect($user->getAttributes())->not->toHaveKey('locale');
    });
});

describe('translation delivery', function (): void {
    it('flattens the English files into file.key.subkey form', function (): void {
        $flat = Translations::flattened();

        expect($flat)->toBeArray()
            ->and($flat)->toHaveKey('common.actions.save')
            ->and($flat['common.actions.save'])->toBe('Save');
    });

    it('carries no Bangla into the frontend copy', function (): void {
        $bangla = collect(Translations::flattened())
            ->filter(fn (string $line): bool => preg_match('/[\x{0980}-\x{09FF}]/u', $line) === 1)
            ->keys()
            ->all();

        // The surviving Bangla is in `settings`, not in the copy files.
        expect($bangla)->toBe([]);
    });
});

describe('plurals', function (): void {
    it('gives every counted string a singular and a plural form', function (): void {
        // The Bangla copy had no plural forms, so nothing needed them and
        // ":count members" rendered "1 members" once the site read in English.
        $counted = collect(Translations::flattened())
            ->filter(fn (string $line): bool => str_contains($line, ':count'));

        expect($counted)->not->toBeEmpty();

        foreach ($counted as $key => $line) {
            expect(substr_count($line, '|'))->toBeGreaterThan(
                0,
                "{$key} uses :count but offers one form only",
            );
        }
    });
});

describe('the names that stay in Bangla', function (): void {
    beforeEach(function (): void {
        $this->seed(SettingsSeeder::class);

        // The service caches the whole table, and it was already read during
        // boot — before the seeder wrote anything.
        app(SettingsService::class)->flush();
    });

    it('keeps the organisation and school names as settings', function (): void {

        foreach (['organization.name_bn', 'school.name_bn'] as $key) {
            expect(setting($key))->toBeString()
                ->and(preg_match('/[\x{0980}-\x{09FF}]/u', (string) setting($key)))->toBe(1);
        }
    });

    it('keeps the Jubilee title and theme line as settings', function (): void {
        foreach (['jubilee.title_bn', 'jubilee.theme_bn'] as $key) {
            expect(preg_match('/[\x{0980}-\x{09FF}]/u', (string) setting($key)))->toBe(1);
        }
    });
});

describe('enum labels', function (): void {
    it('renders a status label rather than its raw value', function (): void {
        expect(MemberStatus::UnderReview->label())->not->toBe('under_review');
    });
});
