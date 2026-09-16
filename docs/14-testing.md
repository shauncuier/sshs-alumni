# 14 — Testing Strategy

Pest 5. The project is not complete without tests, and the suite must be green before any phase is called done.

---

## 1. Philosophy

The platform has a very large surface — 61 tables, 11 roles, three UI surfaces. Uniform CRUD coverage would be enormous and would mostly assert that Laravel works.

Effort concentrates where a bug causes **real harm**:

| Priority | Area                       | Why                                                            |
| -------- | -------------------------- | -------------------------------------------------------------- |
| 1        | Authorization              | a leak exposes thousands of people's personal data             |
| 2        | Privacy filtering          | the member made a choice; breaking it is a betrayal, not a bug |
| 3        | Verification state machine | wrong status = wrong membership number, wrong access           |
| 4        | Payments                   | money must reconcile                                           |
| 5        | Check-in idempotency       | a duplicate at the gate is a real-world argument               |
| 6        | Registration validation    | the front door of the whole platform                           |
| 7        | Localization               | a missing key is a blank screen for a Bangla-first user        |
| 8        | CRUD happy paths           | cheap, useful regression net                                   |

Feature tests over unit tests, as the project conventions require. Unit tests are for pure calculation: `ProfileCompletionCalculator`, `MembershipNumberGenerator`, `BanglaNumber`, SMS segment counting.

## 2. Running

```bash
php artisan test --compact                 # everything
php artisan test --filter=MemberVerify     # one group
php artisan test tests/Feature/Admin       # one directory
vendor/bin/pest --parallel                 # faster locally

composer ci:check                          # lint + Larastan 7 + tests — what CI runs
```

`tests/Pest.php` and `tests/TestCase.php` already exist. `RefreshDatabase` on feature tests; SQLite in-memory for speed locally, plus a MySQL run in CI.

## 3. Test data

Factories for every model, with named states matching real situations:

```php
Member::factory()->pending()->create();
Member::factory()->approved()->withBatch(1990)->create();
Member::factory()->approved()->privateContact()->create();
Event::factory()->jubilee()->dateTba()->create();
Event::factory()->registrationOpen()->create();
Payment::factory()->manual()->paid()->create();
```

Always use factories and their states — never hand-build a model in a test. A state encodes the domain rule; a hand-built model encodes a guess.

Helpers in `tests/Pest.php`:

```php
function actingAsSuperAdmin(): TestCase;
function actingAsRole(string $role): TestCase;
function actingAsMember(array $attributes = []): TestCase;
function actingAsCoordinatorOf(Batch $batch): TestCase;
```

## 4. Mandatory security tests

These are the ones that must never be skipped, deleted or marked incomplete. They are restated from [08-security-privacy.md §12](08-security-privacy.md) because this is where they live.

### Authorization

```php
it('redirects guests away from every admin route', function (string $route) { … })
    ->with(adminRoutes());

it('forbids an authenticated member from every admin route', function (string $route) { … })
    ->with(adminRoutes());

it('forbids a role from routes outside its permissions', function (string $role, string $route) { … })
    ->with(rolePermissionMatrix());
```

The dataset is generated from the route list, so **a new admin route added without a permission fails the suite automatically.** This is the single most valuable test in the project.

### Privacy

```php
it('never exposes a phone number when show_phone is false', function () {
    $member = Member::factory()->approved()->privateContact()->create();

    actingAsMember()
        ->get(route('directory.index'))
        ->assertDontSee($member->mobile)
        ->assertJsonMissingPath('props.members.data.0.mobile');
});

it('omits a member with show_profile false from the directory entirely', …);

it('returns only the six permitted fields from the public verification page', function () {
    // even for a member with every privacy flag set to true
});
```

`assertDontSee` alone is not enough — a field could be present but empty. The payload path assertion is what proves absence.

### Verification

```php
it('assigns a membership number only on approval', …);
it('records every status transition in member_verifications', …);
it('never shows an internal note to the member', …);
it('does not let a rejected member reach the directory', …);
it('generates unique membership numbers under concurrent approval', …);
```

### Payments

```php
it('writes payment, receipt number, audit row, CRM activity and notification when recording offline', …);
it('requires payments.create to record a payment', …);
it('preserves the original row on refund', …);
it('shows a member only their own payments', …);
it('does not create a payment row when a fee is waived', …);
```

### Check-in

```php
it('prevents a duplicate check-in', function () {
    $registration = EventRegistration::factory()->create();

    actingAsRole('Event Manager')->post(route('admin.events.checkin.scan', …));
    $response = actingAsRole('Event Manager')->post(route('admin.events.checkin.scan', …));

    $response->assertSessionHas('warning');
    expect(EventCheckin::where('event_registration_id', $registration->id)->count())->toBe(1);
});
```

### Golden Jubilee

```php
it('renders the Bangla TBA string while the date is not announced', function () {
    $event = Event::factory()->jubilee()->dateTba()->create();

    $this->get(route('jubilee'))
        ->assertSee('তারিখ শীঘ্রই ঘোষণা করা হবে', escape: false);
});

it('does not render a countdown while the date is TBA', …);
it('renders the date and countdown once announced', …);
```

### Uploads & input

```php
it('rejects a PHP file renamed to .jpg', …);
it('strips script tags from rich text before storage', …);
it('ignores mass assignment of status, membership_no and verified_at', …);
it('rate limits registration submission', …);
```

### Localization

```php
it('has matching keys in lang/bn and lang/en', function () {
    // walks both trees and diffs the flattened key sets
});

it('rejects a locale outside the allow-list', …);
it('falls back to the base column when the _bn column is empty', …);
```

## 5. Per-phase coverage

| Phase                      | Tests added                                                                                                                                                                                                           |
| -------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **0 Foundation**           | migrations run on both drivers · every factory creates · settings cache invalidates · media upload + variant generation · audit observer writes · locale middleware resolution order · lang key parity                |
| **1 Auth & registration**  | role/permission seeding idempotent · `Gate::before` · every admin route 403 matrix · all five registration steps · per-step validation · draft persistence · notification dispatched                                  |
| **2 Profiles & directory** | profile update · privacy flags on every surface · completion scoring · full verification state machine · membership number uniqueness · directory search + every filter · coordinator scoping                         |
| **3 Events & Jubilee**     | event lifecycle · publish permission · registration + capacity + waitlist · duplicate registration blocked · QR generation · **duplicate check-in** · TBA date rendering · membership card · public verification page |
| **4 CRM**                  | contact CRUD · pipeline transitions · polymorphic timeline ordering · system activity rows on member events · task assignment + overdue · tag application                                                             |
| **5 Money & people**       | manual payment full side-effect set · refund · receipt uniqueness · fee waiver · donation (incl. anonymous) · sponsor visibility · volunteer assignment · committee display                                           |
| **6 Community**            | post CRUD · comment threading · one reaction per member · report queue · moderation actions · batch scoping · rate limits                                                                                             |
| **7 CMS**                  | publish/unpublish · bilingual fallback · announcement audience + time window · gallery ordering · story review flow · SEO field rendering                                                                             |
| **8 Reports & hardening**  | every report renders and exports · export permission + audit row · global search permission filtering · campaign recipient state machine · **SMS driver is `log` in tests** · sitemap · full security sweep           |

## 6. SMS safety in tests

```php
// config/sms.php
'driver' => env('SMS_DRIVER', app()->environment('production') ? 'bulksmsbd' : 'log'),
```

`phpunit.xml` sets `SMS_DRIVER=log` and `SMS_ENABLED=false` explicitly.

```php
it('never uses the real SMS driver in tests', function () {
    expect(app(SmsChannel::class))->toBeInstanceOf(LogSmsChannel::class);
});
```

**No test run can spend real SMS balance.** Same principle for mail: `MAIL_MAILER=array` in `phpunit.xml`, with `Notification::fake()` for dispatch assertions.

## 7. Static analysis & formatting

Part of the definition of done, not optional:

```bash
vendor/bin/pint --dirty --format agent   # after any PHP change
composer types:check                     # Larastan level 7
npm run check                            # vite-plus lint, denyWarnings + type-aware
npm run types:check                      # tsc --noEmit
```

Larastan stays at **level 7**. It is not lowered to make code pass — the code is fixed.

## 8. CI

```yaml
strategy:
    matrix:
        db: [sqlite, mysql]
```

Both drivers, every run. This is what keeps SQLite development and MySQL production from silently diverging — the failure mode that portability rules are designed to prevent.

Steps: `composer install` → `npm ci` → Pint check → Larastan → frontend lint → `tsc` → `php artisan test`.

## 9. Manual verification

Automated tests do not prove the site looks right or reads correctly in Bengali. Before each phase is called done, at **http://sshs-alumni.test/**:

- Public home page, all sections, both languages
- `/join` — all five steps, validation errors, draft survives a refresh
- `/jubilee` — TBA string present, no countdown
- Login as the seeded admin → verify a member → confirm the notification
- Create and publish an event → register → view the QR pass → check in → **scan again and confirm the duplicate is refused**
- Record an offline payment → confirm the receipt and the audit row
- Switch বাংলা ↔ English on every layout
- Mobile sweep at 375px — no horizontal scroll anywhere
- Dark mode on the admin and member surfaces

## 10. Rules

1. **Never delete or skip a test without approval.** Tests are part of the application.
2. Rerun a test immediately after changing it.
3. Run the narrowest relevant set while working; the full suite at each phase boundary.
4. A failing test is reported honestly with its output — never worked around, never marked incomplete to make a phase look finished.
5. Ask the user to run the complete suite after feature tests pass.
