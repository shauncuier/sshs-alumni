# 06 — Localization (বাংলা / English)

Bilingual from the first line of code, not retrofitted.

**The rule: no user-facing string is ever hard-coded in a React component or a Blade view.**

---

## 1. Locales

| Code | Language | Role                        |
| ---- | -------- | --------------------------- |
| `bn` | বাংলা    | default for the public site |
| `en` | English  | default for the admin panel |

`config('app.locale')` = `bn`, `config('app.fallback_locale')` = `en`.

## 2. Resolution order

`SetLocale` middleware, running before `HandleInertiaRequests`:

```
1. Explicit switch          GET /locale/{locale}  → writes to session + user.locale
2. Session                  session('locale')
3. Authenticated user       $user->locale
4. Config default           config('app.locale')
```

Switching persists for authenticated users (`users.locale`) and for guests (session). The switch route validates against the allow-list and redirects back — it never accepts an arbitrary locale string.

## 3. Translation files

```
lang/
├── bn/
│   ├── common.php        buttons, labels, pagination, generic states
│   ├── public.php        public site copy: hero, nav, footer, sections
│   ├── jubilee.php       Golden Jubilee microsite copy
│   ├── member.php        member area
│   ├── admin.php         admin panel
│   ├── enums.php         every enum value's display label
│   ├── notifications.php notification titles and bodies
│   ├── validation.php    Laravel validation messages
│   └── auth.php          auth messages
└── en/   (same files)
```

### Key naming

`{file}.{section}.{key}` — flat within a section, never deeper than three levels.

```php
// lang/bn/public.php
return [
    'hero' => [
        'headline'     => '৫০ বছরের গৌরবময় পথচলা',
        'event'        => 'সুবর্ণজয়ন্তী ২০২৬',
        'organization' => 'প্রাক্তন ছাত্র-ছাত্রী পরিষদ',
        'school'       => 'সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয়',
        'cta'          => 'সদস্য হোন',
    ],
];
```

Both files must contain the same keys. A test asserts key parity between `bn/` and `en/` so a missing translation is caught in CI rather than at a user's screen.

## 4. Frontend delivery

`HandleInertiaRequests::share()` adds:

```php
'locale'     => app()->getLocale(),
'locales'    => ['bn' => 'বাংলা', 'en' => 'English'],
'translations' => Locale::flattenedFor(app()->getLocale()),
```

Only the **active** locale's map is shipped, so the payload does not carry both languages.

```tsx
import { useTranslation } from '@/hooks/use-translation';

const { t, locale } = useTranslation();

<h1>{t('public.hero.headline')}</h1>
<p>{t('member.profile.completion', { percent: 72 })}</p>
```

`t()` falls back to the key itself when a translation is missing, so a gap is visible rather than blank.

## 5. Database content

Content authored by admins is bilingual by **paired columns**, not a translations table:

```
title / title_bn        body / body_bn        name / name_bn
description / description_bn                  caption / caption_bn
```

**Why paired columns rather than a `translations` table.** Exactly two locales are in scope and both are authored by the same person in the same form. A translations table would add a join to every content read and an EAV shape to every write, for flexibility the association does not need. If a third language is ever required, the migration path is documented in [15-roadmap.md](15-roadmap.md).

The `Translatable` trait resolves them:

```php
// In the model
use Translatable;
protected array $translatable = ['title', 'description'];

// Usage — returns title_bn under bn, falls back to title when empty
$news->title;
$news->getTranslation('title', 'en');
```

Fallback is per-field and non-empty-aware: if `title_bn` is blank, `title` is returned rather than an empty heading.

## 6. Bangla typography

### Fonts

Self-hosted, not CDN-loaded at runtime:

| Script | Family                                       | Weights            |
| ------ | -------------------------------------------- | ------------------ |
| Bangla | **Noto Sans Bengali**                        | 400, 500, 600, 700 |
| Latin  | Instrument Sans (already in the starter kit) | 400, 500, 600      |

```css
/* resources/css/app.css */
@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
    --font-bangla: 'Noto Sans Bengali', 'Hind Siliguri', var(--font-sans);
}

html[lang='bn'] body,
[lang='bn'] {
    font-family: var(--font-bangla);
    line-height: 1.8; /* Bangla needs more leading than Latin */
}
```

`<html lang="{{ app()->getLocale() }}">` is set in `resources/views/app.blade.php`.

### Why line-height matters

Bengali has tall ascenders, deep descenders and the _matra_ headline. Latin-tuned line heights (1.4–1.5) cause visible clipping and crowding. Bangla text uses 1.8; headings 1.4 minimum.

## 7. Numerals

Bangla uses ০১২৩৪৫৬৭৮৯.

```php
// app/Support/BanglaNumber.php
BanglaNumber::convert(1976);        // ১৯৭৬
BanglaNumber::localize(5000, 'bn'); // ৫,০০০
```

```tsx
const n = useBnNumber();
<span>
    {n(1976)} — {n(2026)}
</span>; // ১৯৭৬ — ২০২৬
```

Applied to: dates, counters, statistics, currency, the 1976–2026 milestone, the countdown.

**Not** applied to: membership numbers, transaction references, phone numbers, receipt numbers — identifiers stay in Latin digits in both locales so they can be quoted, searched and typed reliably.

## 8. Dates & currency

- `CarbonImmutable` is already the default date class (`AppServiceProvider`).
- Timezone from settings (`Asia/Dhaka` default), stored UTC, rendered local.
- Dates formatted per locale, with Bangla month names in `bn`.
- Currency: `৳` in `bn`, `BDT` in `en`. `<Money />` handles both.

## 9. Enum labels

Enums never render their raw value.

```php
enum MemberStatus: string
{
    case Pending = 'pending';
    // …

    public function label(): string
    {
        return __("enums.member_status.{$this->value}");
    }
}
```

```php
// lang/bn/enums.php
'member_status' => [
    'pending'      => 'অপেক্ষমাণ',
    'under_review' => 'পর্যালোচনাধীন',
    'approved'     => 'অনুমোদিত',
    'rejected'     => 'প্রত্যাখ্যাত',
    'suspended'    => 'স্থগিত',
    'archived'     => 'সংরক্ষিত',
],
```

## 10. Validation messages

`lang/bn/validation.php` is the full Bengali translation of Laravel's validation file, plus `attributes` mapping every field name to its Bangla label — so `full_name` reports as _পূর্ণ নাম_, not `full name`.

## 11. Email & SMS

Notifications render in the recipient's `users.locale`:

```php
public function toMail($notifiable): MailMessage
{
    return (new MailMessage)
        ->locale($notifiable->locale ?? config('app.locale'))
        ->subject(__('notifications.member_approved.subject'));
}
```

`message_templates` carry `subject`/`subject_bn` and `body`/`body_bn`.

**SMS has a cost consequence:** a Bangla SMS must be sent as `type=unicode` and a Unicode segment is 70 characters against 160 for Latin. See [05-modules.md §13](05-modules.md).

## 12. Translator workflow

The committee can revise copy without a developer touching React:

1. Edit `lang/bn/*.php` or `lang/en/*.php`.
2. `php artisan optimize:clear`.
3. Reload — no rebuild needed for PHP translation files.

Content strings (news, pages, events) are edited in the admin panel and need no deployment at all.

## 13. Tests

- Key parity between `lang/bn` and `lang/en`.
- `SetLocale` honours the resolution order.
- `/locale/{locale}` rejects a locale outside the allow-list.
- Public pages render Bangla by default.
- `Translatable` falls back when the `_bn` column is empty.
- `BanglaNumber` converts digits correctly and leaves identifiers alone.
