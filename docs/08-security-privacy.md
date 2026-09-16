# 08 — Security & Privacy

This platform holds the personal data of thousands of real people — names, phone numbers, addresses, employers, dates of birth, photographs. Privacy is a primary requirement, not a settings page.

---

## 1. Threat model

| Threat                                                  | Mitigation                                                                                                          |
| ------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| Scraping the alumni directory                           | Directory is members-only; ULID identifiers are not enumerable; per-field privacy applied server-side               |
| Privilege escalation to admin                           | Permission checks in policies and route middleware; no role logic in React; every admin route covered by a 403 test |
| Enumerating members by ID                               | Integer PKs never exposed; public routes take ULIDs only                                                            |
| Forged membership card                                  | QR encodes a server-verified ULID; the verification page reads from the database, never from the QR payload         |
| Duplicate / fraudulent event check-in                   | `UNIQUE` constraint on `event_checkins.event_registration_id`                                                       |
| Malicious file upload                                   | MIME + extension allow-list, size caps, image re-encoding, non-guessable storage names, private disk for documents  |
| Stored XSS via community posts / CMS                    | React escapes by default; rich text sanitized server-side against an allow-list before storage                      |
| SQL injection                                           | Eloquent / query builder bindings only; no string-interpolated SQL                                                  |
| Mass assignment                                         | Explicit `#[Fillable]` per model; never `$guarded = []`                                                             |
| CSRF                                                    | Laravel's `web` middleware group (already active)                                                                   |
| Credential stuffing                                     | Fortify login throttling (5/min per email+IP, already configured), 2FA and passkeys available                       |
| Bulk data exfiltration by a staff account               | `reports.export` is a separate permission; every export writes an audit row with the filters used                   |
| Exposure of sponsor agreements / verification documents | Private disk, served only through an authorized controller                                                          |
| Session hijacking                                       | Encrypted cookies, `SESSION_SECURE_COOKIE=true` and `SameSite=lax` in production                                    |
| Leaked SMS / mail credentials                           | `.env` only; never logged, never shared to the frontend, never written into error columns                           |

## 2. Privacy model

### The principle

**A private field is absent from the response payload.** It is not blanked, not `null`ed on the client, not hidden with CSS. If a member has hidden their phone number, no response on any surface contains that phone number.

This is enforced in one place — the API Resource layer — so it cannot drift between the web app and the future API.

### Per-field flags

`member_privacy`, one row per member:

| Flag                 | Default     | Controls                                      |
| -------------------- | ----------- | --------------------------------------------- |
| `show_profile`       | `true`      | appearance in the directory at all            |
| `show_phone`         | **`false`** | mobile, WhatsApp                              |
| `show_email`         | **`false`** | email                                         |
| `show_workplace`     | `true`      | occupation, organization, job title, industry |
| `show_location`      | `true`      | division, district, city                      |
| `show_date_of_birth` | **`false`** | date of birth                                 |
| `show_in_batch_list` | `true`      | appearance on the batch page                  |

Contact details default to hidden. Defaults are privacy-preserving; opting in is a deliberate act by the member.

Full address, emergency contact, student ID and blood group are **never** exposed on any member-facing surface regardless of flags. They exist for the association's administrative use only.

### Resource matrix

| Resource                  | Audience                        | Exposes                                                                                                                            |
| ------------------------- | ------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| `PublicMemberResource`    | anyone, `/verify/member/{ulid}` | name, Bangla name, batch, membership number, photo, verification status. **Nothing else, ever — privacy flags do not widen this.** |
| `DirectoryMemberResource` | approved members                | name, photo, batch, bio, social links + every field its privacy flag permits                                                       |
| `AdminMemberResource`     | `members.view` holders          | full record, minus anything the viewer's scoped policy excludes                                                                    |

```php
// DirectoryMemberResource — the pattern
return [
    'ulid'      => $this->ulid,
    'full_name' => $this->full_name,
    'batch'     => BatchResource::make($this->whenLoaded('batch')),

    $this->mergeWhen($this->privacy->show_phone, fn () => [
        'mobile'   => $this->mobile,
        'whatsapp' => $this->whatsapp,
    ]),

    $this->mergeWhen($this->privacy->show_workplace, fn () => [
        'occupation'   => $this->occupation,
        'organization' => $this->organization,
    ]),
];
```

`mergeWhen` omits the key entirely when the condition is false — the field name does not even appear in the JSON.

### Directory visibility

The directory requires `auth` + `verified` + `member.approved`. The public `/batches` page shows aggregate counts only. A public directory would expose real names and batch years to scrapers even with every contact field hidden.

`settings.privacy.public_directory` can open it later without a code change; the resource layer already handles the anonymous viewer.

## 3. Authorization

### Server-side, always

```php
// Route
Route::middleware('can:members.verify')->post('members/{member}/approve', …);

// Form Request
public function authorize(): bool
{
    return $this->user()->can('members.verify');
}

// Policy — the scoped case
public function view(User $user, Member $member): bool { … }
```

The frontend receives `auth.permissions` to hide buttons. **Hiding a button is cosmetic.** Every protected route is covered by a test asserting 403 for a user without the permission.

### Roles vs permissions

Roles are checked in exactly two places: `Gate::before` for Super Admin, and the three scoped policies. Everything else checks permissions, so the committee can re-cut roles from the admin UI without a deployment.

### Scoped access

A Batch Coordinator holds `members.view` but the policy narrows their reach to their own batch. A Member's access to their own payments is a policy, not a permission. See [04-roles-permissions.md §5](04-roles-permissions.md).

## 4. Authentication

Already provided by Fortify and kept: email/password, email verification, password reset, TOTP two-factor with confirmation, recovery codes, passkeys (WebAuthn), password confirmation for sensitive screens.

Added:

- `users.locale`, `status` (`active` | `suspended` | `disabled`) — a suspended user cannot log in.
- `phone`, `phone_verified_at` — schema for future phone verification; no OTP flow in this build.
- Password rules already enforce, in production only, min 12 characters with mixed case, numbers, symbols and an `uncompromised()` check against known breaches. This is existing starter-kit behaviour and is kept.

### Demo credentials

`DemoSeeder` creates `admin@example.test` / `ChangeMe123!` and **refuses to run when `app()->isProduction()`**.

> **This password must be changed before production.** See [13-deployment.md](13-deployment.md).

## 5. File uploads

| Purpose                  | Types                 | Max   | Disk        |
| ------------------------ | --------------------- | ----- | ----------- |
| Profile photo            | jpg, png, webp        | 2 MB  | public      |
| Gallery / cover / banner | jpg, png, webp        | 5 MB  | public      |
| Sponsor logo             | jpg, png, webp, svg\* | 2 MB  | public      |
| Verification document    | jpg, png, pdf         | 5 MB  | **private** |
| Sponsor agreement        | pdf                   | 10 MB | **private** |

Rules:

1. Validated with `image`/`mimes` **and** a real MIME check — never trusted from the client-supplied `Content-Type`.
2. Raster images are re-encoded through Intervention, which strips EXIF (including GPS coordinates embedded in phone photos) and neutralizes polyglot files.
3. \* SVG is accepted for sponsor logos only, and is sanitized (scripts, external references and event handlers stripped) before storage.
4. Stored under a generated name — the original filename is kept in `media.original_name` for display but never used as a path.
5. `media.is_public = false` routes to the private disk. Private files are **never** served by a direct URL; they go through a controller that runs a policy check.
6. Dimension caps prevent decompression-bomb images.
7. Upload endpoints are rate limited.

## 6. Rich text

CMS bodies and community posts accept rich text. Sanitized **server-side, before storage**, against an allow-list: `p, br, strong, em, u, s, h2–h4, ul, ol, li, blockquote, a[href], img[src,alt], table, thead, tbody, tr, td, th`.

Stripped: `script`, `style`, `iframe`, `object`, `embed`, `form`, every `on*` attribute, `javascript:` and `data:` URLs.

Client-side sanitization is a convenience. The server's copy is the one that counts.

## 7. Rate limiting

See [03-routes.md §5](03-routes.md) for the full table. Summary: authentication 5/min (existing), public forms 6/min, registration submit 5/min, community writes 10–20/min, verification lookup 30/min, check-in scan 120/min, report export 10/min.

## 8. Audit logging

`audit_logs` records who did what to which entity, with before/after values, IP and user agent. Append-only: no `updated_at`, no soft delete.

Audited: member approve / reject / suspend / delete, membership number assignment, payment create / edit / refund, event publish and cancellation, check-in, role and permission changes, user create / disable, content publish and delete, post and comment removal, settings changes, and every report export.

**Only changed attributes are stored**, not the whole row. This keeps the table from becoming a second copy of the database and, critically, prevents password hashes, two-factor secrets and API keys from ever landing in an audit row.

## 9. Secrets

| Secret                      | Location                                                            |
| --------------------------- | ------------------------------------------------------------------- |
| `APP_KEY`                   | `.env` — rotating it invalidates all encrypted cookies and sessions |
| DB credentials              | `.env`                                                              |
| Mail credentials            | `.env`                                                              |
| `BULKSMSBD_API_KEY`         | `.env`                                                              |
| Future payment gateway keys | `.env`                                                              |

Never: committed, logged, shared to the frontend, included in an exception report, or written into `campaign_recipients.error`.

`.env` is already git-ignored. `config/*.php` reads from `env()`; application code reads from `config()` so `config:cache` works in production.

## 10. Production configuration

```env
APP_ENV=production
APP_DEBUG=false                 # non-negotiable — debug leaks env and stack traces
SESSION_SECURE_COOKIE=true      # requires HTTPS
SESSION_ENCRYPT=true
SESSION_SAME_SITE=lax
```

Plus: HTTPS enforced with HSTS, `storage/` and `.env` outside the web root, `php artisan config:cache route:cache view:cache`, `DB::prohibitDestructiveCommands()` already active in production via `AppServiceProvider`, and a Content-Security-Policy header.

## 11. Data retention & member rights

- Members are **soft-deleted**, so an accidental deletion is recoverable.
- A member can request full deletion; an admin performs it, which anonymizes the member record while preserving financial rows (`payments` keeps `payer_name` as a snapshot) — a deleted member must not tear a hole in the ledger.
- Members can export their own profile data.
- `audit_logs` and `payments` are never deleted.

## 12. Required security tests

Non-negotiable coverage in [14-testing.md](14-testing.md):

1. Guest → any admin route → redirect to login.
2. Authenticated non-admin → every admin route → 403.
3. Each role → routes outside its permissions → 403.
4. Batch Coordinator → member/post outside their batch → 403.
5. Member → another member's payments, donations, profile edit → 403.
6. `show_phone = false` → payload contains no phone number, on every directory and public route.
7. `show_profile = false` → member absent from directory results entirely.
8. `/verify/member/{ulid}` → returns only the six permitted fields, even for a fully public member.
9. Duplicate check-in → rejected with a clear message, one row in `event_checkins`.
10. Unapproved member → `/directory`, `/community` → blocked.
11. Upload of a `.php` file renamed `.jpg` → rejected.
12. Rich text containing `<script>` → stored sanitized.
13. Mass assignment of `status`, `membership_no`, `verified_at` through the profile form → ignored.
14. Rate limits return 429 at the documented thresholds.
15. Offline payment recording writes an audit row naming the recording admin.
16. Report export writes an audit row.
