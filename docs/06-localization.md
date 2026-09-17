# 06 — Language & copy

**The platform reads in English.**

It was built bilingual — every text column had a `_bn` twin, a `SetLocale` middleware resolved the request language, and a switcher sat in every layout. The committee decided the site reads in English, so that machinery is gone rather than left dormant. This file records what went, what stayed, and why.

**The rule that survives: no user-facing string is hard-coded in a React component.** Copy lives in `lang/en/*.php` so it can be corrected without touching JSX.

---

## 1. What was removed

| Removed                                        | Was                                                    |
| ---------------------------------------------- | ------------------------------------------------------ |
| `lang/bn/`                                     | Seven Bangla language files, key-for-key with English  |
| `App\Concerns\Translatable`                    | Resolved `title` / `title_bn` by active locale         |
| `App\Http\Middleware\SetLocale`                | Session → user preference → config                     |
| `App\Http\Controllers\Public\LocaleController` | `GET /locale/{locale}`                                 |
| `App\Enums\Locale`                             | `bn` \| `en`                                           |
| `App\Support\BanglaNumber` + `bn_number()`     | ০১২৩ numeral rendering                                 |
| `LocaleSwitcher` component                     | The picker in every layout                             |
| 43 `_bn` columns across 23 tables              | The paired-column translation scheme                   |
| `users.locale`                                 | A per-user preference with one language to choose from |

The column drop is `database/migrations/2026_09_17_000012_drop_bangla_columns.php`. It is a forward migration rather than an edit to the original files, so an existing database does not have to be rebuilt. `down()` restores the columns but **not** their contents.

## 2. What stayed in Bangla

Four strings, and they are **identity, not translation** — they are the names of real organisations and the name of a real event:

| Setting                | Value                                 | Where it renders                   |
| ---------------------- | ------------------------------------- | ---------------------------------- |
| `organization.name_bn` | প্রাক্তন ছাত্র-ছাত্রী পরিষদ           | Brand mark, header, footer         |
| `school.name_bn`       | সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয় | Footer, school pages, Jubilee hero |
| `jubilee.title_bn`     | সুবর্ণজয়ন্তী ২০২৬                    | Jubilee hero                       |
| `jubilee.theme_bn`     | ৫০ বছরের গৌরবময় পথচলা                | Jubilee hero                       |

They live in the `settings` table, so they were never `_bn` columns and the drop migration does not touch them. Each renders with its English transliteration as a secondary line, and each Bangla node carries `lang="bn"` — that attribute is what makes the browser apply Noto Sans Bengali and shape conjuncts correctly.

**Noto Sans Bengali is still loaded**, for exactly those strings. Removing it would render them as boxes.

## 3. Numerals and dates

Latin digits everywhere: `1976`, `2026`, `46 batches`, `17 September 2026`. The Bangla numerals inside `jubilee.title_bn` and `jubilee.theme_bn` are part of those set phrases, typed into the setting — nothing converts them at runtime.

`resources/js/lib/format.ts`:

```ts
formatNumber(value, decimals?)     // 5,000
formatCurrency(amount)             // BDT 5,000.00
formatDate(value, options?)        // 17 September 2026
formatDateTime(value)              // 17 Sep 2026, 14:30
```

Identifiers — membership numbers, receipt numbers, transaction references, phone numbers — are never passed through these. They carry the `tabular-id` class so digits line up when read aloud or compared down a column.

## 4. Copy files

```
lang/en/
├── common.php        buttons, labels, pagination, generic states
├── public.php        public site copy: hero, nav, footer, sections
├── jubilee.php       Golden Jubilee microsite copy
├── member.php        member area
├── admin.php         admin panel
├── enums.php         enum case labels
└── validation.php    Laravel's own, plus custom attribute names
```

Keys are `file.section.key`. A key that does not exist renders as itself — `member.batch.title` on screen — so a gap is visible in a screenshot rather than silently blank.

## 5. Frontend delivery

`App\Support\Translations::flattened()` reads the six frontend files, flattens them with `Arr::dot` into `file.key.subkey` form and caches the result. `HandleInertiaRequests` shares it as `translations`.

Outside production the cache key carries a fingerprint of the files' modification times, so editing a copy file takes effect on the next request. In production the cache is plain and cleared on deploy.

```tsx
const { t } = useTranslation();

t('member.batch.title');
t('common.labels.showing', { from: 1, to: 30, total: 46 });
```

Placeholders are substituted **longest token first**, because `:to` is a prefix of `:total` and a naive pass replaces the wrong one. `replaceAll`, because a line may use a placeholder twice.

### Plurals

English needs them; the Bangla copy did not, which is why "1 members" survived until the switch. `choice()` reads Laravel's pipe syntax:

```php
'count' => 'No members|:count member|:count members',
```

```tsx
const { choice } = useTranslation();

choice('member.directory.count', total, { count: formatNumber(total) });
```

Three parts are zero / one / many; two parts drop the zero case. The chosen form still goes through the same replacement rules, so `:count` behaves identically.

## 6. Database content

Content is stored in one language. `Batch::$name` is `SSC 1994`; `Event::$title` is `Golden Jubilee 2026`. There is no resolution step and no fallback — what is in the column is what renders.

Admin forms that previously had paired English/Bangla inputs now have one field each.

## 7. Enum labels

Enum cases resolve their label through `lang/en/enums.php` rather than returning the raw value:

```php
MemberStatus::UnderReview->label();   // "Under review", not "under_review"
```

`App\Enums\Concerns\HasLabel` does the lookup. A case with no entry falls back to a humanised form of its value, so adding a case never renders `under_review` at a member.

## 8. Email & SMS

Both are English. SMS matters commercially: a Bangla message is Unicode, which is **70 characters per segment against 160 for GSM-7** — roughly 2.3× the cost per message. English keeps campaign costs predictable.

The OTP template is fixed by the vendor and was never translated:

```
Your {Brand} OTP is XXXX
```

## 9. Tests

`tests/Feature/Foundation/CopyTest.php` asserts:

- `lang/bn` does not exist and `locale.switch` is not a registered route
- the Inertia payload carries `translations` but no `locale` or `locales`
- a `User` can be created with no language preference
- **no line of frontend copy contains a Bangla character** — if one appears, it belongs in `settings`, not in a copy file
- the four surviving Bangla settings are present and actually contain Bangla

## 10. If Bangla comes back

It would not be a revert. The honest path is a `content_translations` table rather than paired columns, resolved in the API Resource layer where privacy filtering already happens — one join on read instead of 43 columns that are null for most rows. The `t()` indirection and the copy files are the part worth keeping, and they are still here.
