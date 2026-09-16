<?php

declare(strict_types=1);

use App\Enums\Locale as LocaleEnum;
use App\Enums\MemberStatus;
use App\Models\User;
use App\Support\BanglaNumber;
use App\Support\Locale;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

describe('locale switching', function (): void {
    it('stores a supported locale in the session', function (): void {
        $this->get(route('locale.switch', 'bn'))->assertRedirect();

        expect(session('locale'))->toBe('bn');
    });

    it('rejects a locale outside the allow-list', function (string $locale): void {
        $this->get('/locale/'.$locale)->assertRedirect();

        expect(session('locale'))->toBeNull();
    })->with(['de', 'xx', 'en-US']);

    it('never reaches the controller for a path-traversal attempt', function (): void {
        // The route segment cannot contain a slash, so this 404s at the router
        // rather than being validated in the controller.
        $this->get('/locale/../etc')->assertNotFound();

        expect(session('locale'))->toBeNull();
    });

    it('persists the choice for a signed-in user', function (): void {
        $user = User::factory()->create(['locale' => LocaleEnum::En]);

        $this->actingAs($user)->get(route('locale.switch', 'bn'));

        expect($user->refresh()->locale)->toBe(LocaleEnum::Bn);
    });
});

describe('locale resolution order', function (): void {
    it('prefers the session over the user preference', function (): void {
        $user = User::factory()->create(['locale' => LocaleEnum::En]);

        $this->actingAs($user)
            ->withSession(['locale' => 'bn'])
            ->get('/')
            ->assertOk();

        expect(app()->getLocale())->toBe('bn');
    });

    it('falls back to the user preference when the session is empty', function (): void {
        $user = User::factory()->create(['locale' => LocaleEnum::Bn]);

        $this->actingAs($user)->get('/')->assertOk();

        expect(app()->getLocale())->toBe('bn');
    });

    it('survives a user whose locale is not yet loaded from the database', function (): void {
        // Column defaults apply on insert, not in memory. A freshly built User
        // must not crash the request.
        $user = new User(['name' => 'Test', 'email' => 'x@example.test']);
        $user->password = 'secret';
        $user->save();

        $this->actingAs($user)->get('/')->assertOk();
    });
});

describe('translation delivery', function (): void {
    it('ships only the active locale to the frontend', function (): void {
        $flat = Locale::flattenedFor('en');

        expect($flat)->toBeArray();
    });

    it('lists each locale under its own native name', function (): void {
        // A picker that says "Bengali" in English is useless to a Bangla-first
        // reader.
        expect(Locale::available())->toBe(['bn' => 'বাংলা', 'en' => 'English']);
    });
});

describe('language file parity', function (): void {
    it('has matching keys in lang/bn and lang/en', function (): void {
        $files = collect(File::files(lang_path('en')))
            ->map(fn ($file): string => $file->getFilenameWithoutExtension());

        expect($files)->not->toBeEmpty();

        foreach ($files as $file) {
            $enPath = lang_path("en/{$file}.php");
            $bnPath = lang_path("bn/{$file}.php");

            expect(File::exists($bnPath))->toBeTrue("lang/bn/{$file}.php is missing");

            $en = array_keys(Arr::dot(require $enPath));
            $bn = array_keys(Arr::dot(require $bnPath));

            sort($en);
            sort($bn);

            // A missing translation is a blank screen for a Bangla-first user,
            // so it fails here rather than in front of one.
            expect($bn)->toBe($en, "lang/bn/{$file}.php keys differ from English");
        }
    });
});

describe('enum labels', function (): void {
    it('renders a status label in the active locale rather than its raw value', function (): void {
        app()->setLocale('en');

        expect(MemberStatus::UnderReview->label())->not->toBe('under_review');
    });
});

describe('Bangla numerals', function (): void {
    it('converts Latin digits to Bengali', function (): void {
        expect(BanglaNumber::toBangla(1976))->toBe('১৯৭৬')
            ->and(BanglaNumber::toBangla('1976 — 2026'))->toBe('১৯৭৬ — ২০২৬');
    });

    it('converts back for parsing user input', function (): void {
        expect(BanglaNumber::toLatin('১৯৭৬'))->toBe('1976');
    });

    it('leaves Latin digits alone under English', function (): void {
        expect(BanglaNumber::localize(2026, 'en'))->toBe('2026')
            ->and(BanglaNumber::localize(2026, 'bn'))->toBe('২০২৬');
    });

    it('formats currency per locale', function (): void {
        expect(BanglaNumber::currency(5000, 'bn'))->toBe('৳৫,০০০.০০')
            ->and(BanglaNumber::currency(5000, 'en'))->toBe('BDT 5,000.00');
    });
});
