# Changelog

Phase-by-phase record of the SSHS Alumni Platform build. Newest first.

Format loosely follows [Keep a Changelog](https://keepachangelog.com/). Dates are ISO (YYYY-MM-DD).

---

## Phase 6 — Community · 2026-09-18

**Status: complete**

### Added

- **The feed** — posts in nine categories, filtered, searched, paginated; pinned first, then whatever was last talked on.
- **Comments**, threaded one level. **Reactions** — four kinds, one per member per item. **Photos** — up to four per post, re-encoded through the existing media pipeline.
- **Mentions** — `@member:{ulid}`, resolved at render time.
- **Reports and a moderation queue** — two admin screens, every action audited with its reason.

### Two rules decide what a member sees, and they live on the model

`Post::scopeVisibleTo()` for lists and `Post::isVisibleTo()` for a single row are the same two rules written twice, on the model, so the feed and the post page cannot drift apart. A controller that reimplemented the batch rule would be exactly where they drifted.

1. **Published only — unless you wrote it.** An author still reads their own hidden post. Being moderated is not the same as being lied to about whether your words still exist, and the post carries a notice saying a moderator has hidden it.
2. **A post with a `batch_id` belongs to that batch.** Batch discussion is the one place members expect a smaller room than the whole association.

A post the reader may not see is a **404, not a 403**. Confirming that a batch post exists is itself a small disclosure.

### A moderator cannot edit a member's words

`PostPolicy::update()` is the author and nobody else — there is no moderator branch in it, and a test asserts a Moderator gets a 403 from the edit endpoint. Moderators hide, remove, pin and close comments. All four are visible, all four are reversible, and all four are audited. **Nothing in this application publishes different words under somebody else's name.**

Hiding and removing are different: hidden leaves a post readable by its author and by moderators, removed takes it from everyone but moderators. Neither deletes the row — a member who complains that their post disappeared is owed an answer, and an empty table cannot give one.

### Reporting is not moderation

Filing a report hides nothing and tells nobody else. It puts the item in a queue. **If a report took content down on its own, the community would have been handed a delete button for anybody with a grudge.**

- The same member reporting the same item twice creates nothing — the second report tells a moderator no more than the first.
- Reporting your own post is a 403. The author can simply delete it.
- The reporter gets the same message either way. Learning that somebody else already reported a post tells you something about another member's opinion of it, which is not yours to know.
- Closing a report closes **every other open report on the same item**, so two moderators cannot work the same post twice.
- **`dismissed` is a first-class outcome.** Most reports of a heated batch argument are somebody wanting the argument to stop; recording that a moderator looked and decided nothing was wrong is exactly as valuable as recording a removal. Reports are never deleted — a queue that can be emptied by deleting the awkward entries is not a record of anything.

### Where the privacy line falls here, and why it is not the directory's line

Posting is a public act inside the community: you cannot write under a name nobody may see. So the **name is always present**. What a member controls is what follows from it — whether their face appears beside their words, and whether their name is a door into the profile they closed. `photo_url` and `url` are **absent** from the payload for a hidden member, not null, in keeping with the rest of the resource layer.

The same rule governs mentions: an unresolvable `@member:{ulid}` renders as the literal characters the author typed. That is the honest failure — it says somebody was mentioned without inventing who.

### Counters that cannot drift

`posts.comments_count` and `posts.reactions_count` are maintained by observers on `Comment` and `Reaction`, and they **recount** rather than increment.

A counter incremented on create and decremented on delete is correct only while those are the only two things that happen, and they are not: a moderator hides a comment, a soft-deleted comment is restored, comments go one by one from a moderation queue. Every path would have to remember, and the first one that forgot would leave a post permanently claiming four comments while showing three. A recount is one indexed aggregate over a handful of rows.

`last_activity_at` is bumped by a comment and **not** by a reaction — a post should not climb back to the top of the feed because one person tapped a heart on it.

### Rendered as text, never as markup

The body is member-authored, so there is no `dangerouslySetInnerHTML` anywhere in the community components and there must never be one. The only thing that becomes a link is a mention, matched against a fixed `@member:{ulid}` pattern and looked up in a map the server built — never constructed from the text itself.

### Smaller decisions worth their comment

| Decision                                    | Why                                                                                                                                                                                              |
| ------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| One reaction per member, enforced by UNIQUE | Two taps on a phone with a bad connection arrive as two requests; a check-then-insert loses that race. Same pattern as event check-in.                                                           |
| A reply to a reply re-points at the parent  | One level deep, because a thread that nests forever is unreadable on the phone most of this association reads it on. The member did nothing wrong, so their words are kept rather than rejected. |
| The post's author can delete a comment      | Somebody who starts a thread is responsible for it. Waiting on a moderator to remove an insult under your own memorial post is not a reasonable ask.                                             |
| A batch post goes to the author's own batch | There is no field for choosing one. Posting into a cohort you did not attend is not a thing this application does.                                                                               |
| Category and batch are not editable         | Moving a post between rooms after people have replied changes who can read the replies.                                                                                                          |
| Posts are not `Auditable`                   | Every member fixing their own typo would write an audit row, and the moderation record would be buried in ordinary traffic. Moderation writes its own row, with the reason.                      |

### Verified

- **449 tests, 1,618 assertions** (397 → 449). PHPStan level 7, TypeScript and the frontend linter clean.
- 37 new tests across three files: the feed and its two visibility rules, reactions and reporting and the queue, and mentions and the author payload.
- `PaginationShapeTest` now covers the three new paginated pages, so the envelope bug that shipped three times cannot come back through this phase.
- Two tests were flaky before they were committed, both for the same reason: `batches.ssc_year` is UNIQUE and `BatchFactory` picks a year at random, so a test creating a batch per member collided roughly one run in fifteen. Fixed by sharing one batch where the batch does not matter and pinning explicit years where it does.

**The authenticated pages were not driven in a browser this time.** The demo database was rebuilt during the phase, and signing back in means typing a password, which is not something I do. The rendering is covered by the suite — every page is requested through the real Vite manifest, so a missing or misnamed component is a 500 — but nobody has looked at the layout. Sign in as `member@example.test` and open `/community` if you want that pass.

### Not done in this phase

**The public home page is still the Laravel starter `welcome` page.** `/` renders `resources/js/pages/welcome.tsx` — the association's front door was never built, and it is the first thing Phase 7 does. Mention notifications wait for Phase 8 with the rest of the notification layer.

---

## Phase 5 — Money, volunteers, committees · 2026-09-18

**Status: complete**

### Added

- **The ledger** — one `payments` table for every kind of money. Fees, event registrations, donations and sponsorships all resolve through `payments.payable`, so no two reports can disagree about income.
- **Gateway abstraction** — `PaymentGateway` contract, `ManualGateway`, `PaymentManager`, `config/payments.php`. Adding bKash, Nagad or SSLCommerz later is one class plus one config entry; nothing outside the namespace knows which driver is in use.
- **Membership fees** — raised per period in bulk, paid, or waived.
- **Donations** — recorded with or without a donor record, campaign-tagged, with a public wall.
- **Sponsors and packages** — invoiced before payment, with slot counts so a tier is not promised twice.
- **Volunteers, teams and assignments**; **committees** and who sits on them.
- **Public pages** — `/donate`, `/sponsorship`, `/committees`. **Member pages** — payments, donations, and a printable receipt.

### The ledger's invariants

**Payments are never edited into a different amount and never deleted.** A mistake is corrected by refunding and re-recording, and both are audited. There is no `update` and no `delete` on `PaymentPolicy`, no route for either, and a test walks the live route list to prove it — a policy method for editing one would only invite a controller to exist for it.

A refund preserves the original row **and its receipt number**. A ledger that erases its mistakes cannot be audited.

**Every path that takes money goes through `PaymentRecorder`**, so the six things that must happen together cannot be half-done: the ledger row, the receipt number, the payable's own status, the audit row naming whoever recorded it, the CRM timeline entry, and (Phase 8) the notification. Skipping the audit row would make offline cash handling untraceable, which is the single largest financial risk in a volunteer-run organization — so it is not the caller's job to remember.

### The `Payable` contract

`MembershipFee`, `EventRegistration`, `Donation` and `Sponsor` each implement it. **The payable settles itself**, inside the recorder's transaction, so a fee can never read `paid` while the ledger row that paid it is missing — and adding a fifth kind of payable never means editing the recorder.

`markUnpaid()` is deliberately asymmetric: a refunded fee goes back to `pending` (the member still owes it), a refunded sponsorship goes back to `confirmed` (the agreement still stands).

### Waiving is not paying

A volunteer-run association waives fees routinely — a founding member, someone in hardship, a teacher. Waiving sets `waived` and **writes no payment row**, because no money was received and the accounts must not claim it was. The reason is required: a waiver with no reason is indistinguishable from an oversight. The fees page shows waived separately from collected for the same reason.

### Receipt numbers

`RCP-2026-0001`, sequential within a calendar year, generated inside a transaction with a row lock. Two administrators recording cash at the same desk at the same moment is not hypothetical, and a duplicate receipt number is discovered a year later by somebody who cannot fix it.

The generator reads the **highest existing number** rather than counting rows — a refunded payment keeps its number, so a count would eventually collide. There is a test for exactly that.

### Honest about what the platform does not do

`/donate` takes no card. The association collects in cash, by transfer and through mobile financial services settled outside the platform, so the page says how to give and the office records it. A payment button that did not work would be worse than honest instructions.

The public donor wall is **opt-in twice over** — received, not anonymous, and marked public — and anonymous wins over everything. Somebody who asked not to be named must not be named because a checkbox elsewhere said otherwise. Three tests cover those three doors.

Standing a committee member down keeps the row, marked `past` with an end date: a committee's history is part of the association's record, and deleting it would make the 2019 committee unreconstructable.

### Bugs found and fixed during the phase

| Bug                                             | Why it mattered                                                                                                                                                                                                                                                       |
| ----------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Five pages returned a bare paginator, AGAIN** | Third occurrence of this exact bug, in the third phase running. `->through()` keeps the page numbers at the top level; the Pagination component reads `meta` and throws. Found in the browser, invisible to every data assertion. Now fixed structurally — see below. |
| `$payment->payerMember` does not exist          | The relation is `payer`. Written from the column name rather than from the model.                                                                                                                                                                                     |
| `Donation::$donorMember` does not exist         | Same mistake, same cause — the relation is `donor`.                                                                                                                                                                                                                   |
| Four more `?->` on the left of `??`             | PHPStan enforces it across the codebase now. `Sponsor::amountDue()` was rewritten as explicit branches, because a triple `??` chain over a negotiated amount, a package price and a default is worth reading rather than parsing.                                     |
| `CrmContact::find()` widening to a Collection   | Third occurrence of this one. `whereKey(...)->first()` says what was meant.                                                                                                                                                                                           |

### The pagination shape, finally nailed down

This bug has now shipped in Phase 2, Phase 3 and Phase 5, because the broken
shape is invisible to a `->has('rows.data', 3)` assertion — the data is all
there, only the envelope is wrong, and the page throws on render.

Two things changed so it cannot happen a fourth time:

- `App\Support\Paginated::from()` builds the `{data, meta}` envelope for every
  controller that maps rows inline. Controllers with a Resource keep using the
  Resource.
- `tests/Feature/Foundation/PaginationShapeTest.php` walks **every** paginated
  admin and member page and asserts `meta.last_page`, `meta.total` and
  `meta.links`, and greps the controllers for a remaining `->through()`.

### Verified, not assumed

- **397 tests, 1,288 assertions** (369 → 397; the full suite, run at the phase boundary). PHPStan level 7, TypeScript and the frontend linter clean.
- The admin payments, fees, donations, sponsors, volunteers and committees pages and the three public pages were opened in a real browser.

### Not done in this phase

`PaymentReceived` notifications: the notification layer arrives in Phase 8, and the hook is step six of `PaymentRecorder`. Receipts print through the browser rather than dompdf — Bengali conjunct shaping in dompdf is unreliable and the receipt carries the association's Bangla name, which is exactly the string that would break.

---

## Phase 4 — CRM · 2026-09-18

**Status: complete**

### Added

- **Contacts** — prospects, volunteers, donors, sponsors, guests, partners and organizations. Search, filter by type, stage, owner and tag; a stage strip that doubles as a one-click filter.
- **Pipeline board** — eight columns, drag to advance, and a select on every card so it works without a mouse. Columns cap at 25 cards with "and N more" linking into the filtered list; a column showing four hundred contacts is a list with extra scrolling, not a board.
- **Activity timeline** — one polymorphic feed over members and contacts.
- **Tasks** — hanging off a contact, a member, or nothing at all. `is_overdue` is computed server-side.
- **Tags** — polymorphic over members and contacts, colour-validated as hex.
- **Owner assignment**, member↔contact linking, and CRM counts on the admin dashboard.

### The timeline is a union, not a list

A person may exist as a member AND as a contact — entered as a prospect, later registering, the two records linked. Their history is then split across two subjects.

A timeline showing one half would be **worse than no timeline**: it would look complete while hiding the call that preceded the registration. So `ActivityLogger::timelineFor()` resolves both subjects and orders the union, and the feed reads identically from either end.

Unlinking does **not** rewrite history. The activities stay where they were written — moving them to follow a correction would lose the record of the mistake.

### `system` rows write themselves, and cannot be forged

They are written by the services that do the thing — `VerificationService` on a status change, `EventRegistrar` on a registration, `PipelineService` on a stage move or an owner handover — not by callers remembering to.

**`system` is absent from the type list a form may post.** Those rows are the platform vouching that something happened; anyone able to write one could fabricate a history. A test asserts the endpoint rejects it.

Activities may be backdated (a call made yesterday is real) but not postdated.

### The pipeline is not a state machine

Membership verification is. A relationship is not: a donor who went quiet may be moved straight back to `contacted`, and a prospect who walks in already registered skips three stages. Constraining that would make the committee fight the tool.

What is enforced is that every move is **recorded**. The ordinary edit form strips `pipeline_status` and `owner_id`, so neither can change without going through `PipelineService` and landing on the timeline — a test posts a stage through the edit endpoint and asserts it does not move.

A move to the stage something is already at writes nothing.

### Ownership answers "whose job", not "who may look"

Reading is deliberately not owner-scoped: a volunteer coordinator needs to see that the membership secretary already called someone, and a contact only one person can see is a contact only one person follows up.

Taking a contact off a colleague's list needs `crm.assign`; the owner can always hand it on themselves. A new contact belongs to whoever entered it, and the dashboard counts the unowned ones — an unowned contact is how a prospect goes cold.

### Bugs found and fixed during the phase

| Bug                                             | Why it mattered                                                                                                                              |
| ----------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| A re-run of a patch script duplicated an import | `VerificationService` ended with `use ActivityLogger;` twice — a fatal error, caught immediately because the script was not idempotent.      |
| `User::query()->find()` widened to a Collection | PHPStan: `find()` accepts an array, so the return type is not just `?User`. `whereKey(...)->first()` says what was meant.                    |
| `__()` returns `array\|string`                  | The registrar passed one straight into a `?string` parameter.                                                                                |
| The plurals test was too strict                 | "and 1 more" and "and 3 more" are both correct English. The rule now lists genuinely invariant phrases rather than forcing a duplicate form. |

### Verified, not assumed

- `/admin/crm/contacts`, `/pipeline`, `/tasks`, `/tags` and the dashboard driven in a real browser; no console errors. A stale bundle produced a "Page not found" resolver error on the first load — a rebuild cleared it, and it is noted here because it looks like a routing bug and is not.
- **369 tests, 1,166 assertions.** PHPStan level 7, TypeScript and the frontend linter clean.

### Not done in this phase

Donation and payment entries on the timeline wait for Phase 5, where those services exist. The specification's example flow shows a donation row; the hook is `ActivityLogger::system()` and the caller will be `PaymentRecorder`.

---

## Phase 3 — Events, Golden Jubilee, QR passes, check-in · 2026-09-17

**Status: complete**

### Added

- **Events** — admin list, detail, create and edit; a six-state lifecycle; ticket types with a maintained `sold_count`; public listing and detail pages.
- **Registration** — members register themselves with accompanying guests; the office records walk-ins for people with no alumni record. Guests occupy seats and are charged for them.
- **Waitlist** — capacity produces a waitlist entry, never a rejection. Turning an alumnus away automatically is the wrong default for a reunion, and the committee can promote past capacity because that is their call to make.
- **QR passes** — `App\Support\Qr` renders inline SVG through `bacon/bacon-qr-code`. SVG needs no imagick or GD, which is one less thing to be missing on the production host on event day, and it stays sharp when someone zooms into their phone at the gate.
- **Check-in** — a full-width gate screen with large tap targets, live counts and the last ten admissions. Behind `events.checkin`, which a volunteer holds _without_ `events.edit`.
- **Golden Jubilee microsite** — `/jubilee`, `/jubilee/schedule`, `/jubilee/sponsors`, `/jubilee/faq`. Not a subsystem: every page resolves the flagship event, so `is_flagship` can move to a future event and the microsite follows the flag. Nothing here becomes dead code on 1 January 2027.
- **Digital membership card** — `/my/card`, with a QR pointing at `/verify/member/{ulid}`. The card shows exactly the six fields a scanner sees, so there are no surprises at the gate.

### The date rule, enforced in three places

The committee has not fixed a Jubilee date. Rather than trusting every component to remember that:

1. **The resource omits it.** `PublicEventResource` does not serialise `starts_at` at all while `date_status` is `tba`. A component cannot leak a date it was never given.
2. **The type says so.** `starts_at` is optional on `PublicEvent`, so TypeScript refuses code that reads it unconditionally.
3. **Announcing is a separate act.** Saving a draft date through the ordinary edit form does not announce it; `date_status` moves only through `POST /admin/events/{event}/date`, behind `events.publish`. It can be retracted, because a date that has to be pulled back is exactly when the "to be announced" line matters most.

`JubileeCountdown` returns `null` while the date is unannounced — no DOM node, so nothing for a screen reader to read, nothing in a screenshot, and no styling change that could reveal a date that does not exist yet. Verified in the browser: `[role=timer]` is absent and `data-date-status` reads `tba`.

A test greps `config/` and `database/seeders/` for a `2026-MM-DD` literal and fails if one appears.

### Duplicate check-in

Prevented by the `UNIQUE` constraint on `event_checkins.event_registration_id`, not by an application `if`. Two volunteers scanning the same pass at two gates in the same second is exactly the case a read-then-write check loses, and exactly the case that causes an argument in front of a queue. The insert is attempted and a constraint violation is read as "already checked in" — which is the truth, whoever won the race.

The test that matters goes **straight at the table**, past every service and controller, and asserts the second insert throws.

Scanning is a GET that SHOWS the operator who is in front of them; admitting is a separate POST. A camera pointed at a wall of passes would otherwise admit all of them.

### Bugs found and fixed during the phase

| Bug                                                    | Why it mattered                                                                                                         |
| ------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------- |
| **`qr_token` generated after the insert**              | The column is `NOT NULL`, so every registration failed at the database. Now set in the same insert, still not fillable. |
| **`$ticket->currency` without the nullsafe operator**  | A fatal error on any event with no ticket types. PHPStan found it while the code was still unreachable.                 |
| `JubileeController::sponsors()` declared twice         | A public route method and a private helper with one name — a fatal parse error.                                         |
| `useForm().transform()` is not chainable in Inertia v3 | It returns void. Three forms posted nothing.                                                                            |
| Pass URL built from the event slug                     | Admin routes bind Event by ULID, so every QR pointed at a URL that would not resolve.                                   |
| Tokens that do not exist (`brand-gold-300/400`)        | Tailwind generates nothing for an undeclared token, so the countdown would have rendered unstyled.                      |
| "Next" as the end-date label                           | A placeholder key that shipped into the admin form. Found by reading the rendered page, not the code.                   |

### Verified, not assumed

- `/jubilee`, `/events`, `/admin/events` and an event's detail page driven in a real browser. No console errors; the date line reads "Date to be announced" and no countdown element exists.
- The Bangla identity strings render on the Jubilee hero with `lang="bn"`, and the computed font resolves to Noto Sans Bengali on exactly those nodes.
- **341 tests, 1,088 assertions.** PHPStan level 7, TypeScript and the frontend linter clean.

### Not done in this phase

The Jubilee programme schedule renders "to be announced" and holds no sessions — the committee has not built one, and inventing a table for it before they do would be guessing at its shape. Payment for a paid registration records an amount owed; taking the money is Phase 5.

---

## Language change — English-only · 2026-09-17

**Status: complete**

The platform was built bilingual. The committee decided the site reads in English, so the translation machinery was removed rather than left dormant.

### Removed

| Removed                                        | Was                                                    |
| ---------------------------------------------- | ------------------------------------------------------ |
| `lang/bn/`                                     | Seven Bangla language files, key-for-key with English  |
| `App\Concerns\Translatable`                    | Resolved `title` / `title_bn` by active locale         |
| `App\Http\Middleware\SetLocale`                | Session → user preference → config                     |
| `App\Http\Controllers\Public\LocaleController` | `GET /locale/{locale}`                                 |
| `App\Enums\Locale`                             | `bn` \| `en`                                           |
| `App\Support\BanglaNumber` + `bn_number()`     | ০১২৩ numeral rendering                                 |
| `LocaleSwitcher`                               | The picker in every layout                             |
| 43 `_bn` columns across 23 tables              | The paired-column translation scheme                   |
| `users.locale`                                 | A per-user preference with one language to choose from |

`App\Support\Locale` became `App\Support\Translations`, which now does one thing: flatten `lang/en` for the frontend.

### Kept in বাংলা

Identity, not translation — real names of real organisations and of a real event. All four live in `settings`, so the column drop never touched them:

`organization.name_bn` · `school.name_bn` · `jubilee.title_bn` · `jubilee.theme_bn`

Plus `school.motto_bn` (জ্ঞানই শক্তি), with `school.motto_en` alongside it. Each renders with its English line underneath, and each Bangla node carries `lang="bn"` — **Noto Sans Bengali is still loaded**, for exactly these strings.

`school.board_bn` and `school.chairperson_bn` became `school.board` and `school.chairperson` in English: an education board and an office holder are not the association's own identity.

### Bugs found and fixed during the change

| Bug                                                       | Why it mattered                                                                                                                                                                 |
| --------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **The strip script ate the settings it was told to keep** | `organization.name_bn` and the Jubilee title and theme match the same `'foo_bn' => [...]` shape as the columns being removed. Caught by the test that asserts they are present. |
| **`$ticket->currency` without the nullsafe operator**     | A fatal error on any event registration with no ticket type. Found by PHPStan while it was still unreachable code.                                                              |
| **"1 members"**                                           | The Bangla copy had no plural forms, so nothing needed them. `choice()` now reads Laravel's pipe syntax.                                                                        |
| `APP_LOCALE=bn` in `.env`                                 | Laravel's own framework strings kept coming back in Bangla — the migration output read `6 সেকেন্ড DONE`.                                                                        |

### Verified, not assumed

- `/batches` driven in a real browser: English copy, Latin digits, no console errors.
- A test asserts **no line of frontend copy contains a Bangla character**. If one appears it belongs in `settings`, not in a language file.
- **282 tests, 857 assertions.** PHPStan level 7, TypeScript and the frontend linter clean.

### Note

The column drop destroys the Bangla text those columns held — batch names, the Jubilee event's `title_bn`, demo member names. `down()` restores the columns but not their contents. Stated before it was run.

---

## Phase 2 — Profiles, verification, batches, directory · 2026-09-17

**Status: complete**

### Added

- **Member profile** — full self-service edit, links, privacy panel, completion scoring, and photo upload re-encoded by `MediaService`. The form request narrows what may be written: status, membership number and verification are absent from its rules, so they cannot be set however the payload is shaped.
- **Verification workflow** — a `TRANSITIONS` state machine, every transition recorded with actor and note, request-for-correction, and batch-scoped membership numbers (`SSHS-{SSC_YEAR}-{SEQ}`) issued under `lockForUpdate()`.
- **Alumni directory** — members-only, seven filters (batch, district, industry, country, occupation, relation, blood group), grid and list views, and a profile page bound by ULID so profiles cannot be walked.
- **Privacy resources** — `PublicMemberResource`, `DirectoryMemberResource`, `AdminMemberResource`. Hidden fields are ABSENT from the payload, not blanked, and privacy defaults closed when the row is somehow missing.
- **Batches** — admin list, detail and editing; coordinator assignment restricted to approved members of that batch; a member-facing "my batch" page honouring the extra `show_in_batch_list` opt-out.
- **Server-computed navigation** — `App\Support\Navigation` builds the admin, member and public menus from route NAMES, filtered by `Route::has()` and permission. An unshipped phase cannot render a link, and the moment a phase lands its links appear with no component change.

### Bugs found and fixed during the phase

| Bug                                                       | Why it mattered                                                                                                                                                                                                              |
| --------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Controller` no longer carries `AuthorizesRequests`       | Laravel 11 removed it from the base class. Every `$this->authorize()` in an admin controller was a fatal error — reported from the running site, not caught by the suite.                                                    |
| **34 dead navigation links**                              | 19 in the admin sidebar, 6 in member nav, 9 in the public header and footer, all pointing at routes that do not exist yet. Fixed structurally rather than by deleting links.                                                 |
| **`/admin/batches` was reachable by any approved member** | The `Member` role holds `batches.view` so it can read the PUBLIC batch pages. Admin routes were gated per module only, so that permission also opened the admin panel. `can:admin.access` now guards the whole group.        |
| **Pagination read page links from the wrong place**       | Laravel puts the numbered links inside `meta`; a paginated resource's top-level `links` is a `{first,last,prev,next}` OBJECT. `links.slice` threw and the whole page rendered blank — on the member list too, at 74 members. |
| **`t()` replaced `:to` inside `:total`**                  | Produced `মোট ৩০tal টির মধ্যে ১–:to দেখানো হচ্ছে`. Placeholders are now replaced longest-first, and with `replaceAll`.                                                                                                       |
| **Translation cache was `rememberForever`**               | Editing a lang file showed the old string with no hint why. Outside production the cache key now carries a fingerprint of the files' modification times.                                                                     |
| Admin member URLs 404'd                                   | `HasUlid` makes `ulid` the route key; the admin links were still built from integer ids.                                                                                                                                     |

### Verified, not assumed

- Privacy was checked by reading real payloads, not only by green assertions: a member with `show_phone = false` produces a response with no phone key under every route that serialises them.
- `/admin/batches`, `/admin/batches/{id}` and `/admin/members` were driven in a real browser. Three of the bugs above were found that way and would not have failed the suite.
- The navigation test asserts every route name the menus declare either resolves or is on an explicit "not built yet" list, so a typo can no longer hide as a silently skipped item.
- **289 tests, 873 assertions. PHPStan level 7, TypeScript and the frontend linter all clean.**

### Not done in this phase

The digital membership card and `/verify/member/{ulid}` QR target belong to Phase 3, where the QR generator lands. The public site's own pages (home, about, contact, news, gallery) are Phase 3 and 7; until then the public header carries only the links that exist.

---

## Phase 1 — Auth, roles, admin shell, registration · 2026-09-17

**Status: complete**

### Added

- **Layouts** — `PublicLayout` (pinned light by a `theme-light` class, so one tab can show a light public page and a dark admin page), `MemberLayout` (horizontal nav for a phone-first audience), `AdminLayout` (the starter shell with our permission-filtered sidebar).
- **Registration** — five steps validated one at a time, the draft held in the session so a refresh does not discard four screens of typing. Academic fields are required for former students and optional otherwise. The password is hashed the moment it validates and never enters the draft.
- **RBAC UI** — the committee can re-cut roles without a deployment, which is why application code checks permissions rather than role names. Super Admin and Member are protected.
- **The route-protection sweep** — walks the REAL route list and fails if any admin route lacks `auth` or a `can:` permission, then sweeps every admin route as a guest, as a member and as a Super Admin.

### Bugs found and fixed during the phase

| Bug                                   | Why it mattered                                                                                                                                                                                              |
| ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `app.tsx` double-wrapped pages        | The starter sidebar was applied to every page, including pages carrying their own layout.                                                                                                                    |
| Bangla labels rendered white-on-white | Dark mode was active while the public surface hardcoded white backgrounds.                                                                                                                                   |
| The first fix for that did not work   | Tailwind v4 `@theme` declares `--color-card: var(--card)` at `:root`, so the substitution resolves once. Overriding `--card` downstream does nothing — the override has to target the `--color-*` namespace. |

### Verified, not assumed

- **196 tests, 472 assertions** at the close of the phase. PHPStan level 7, TypeScript and the frontend linter clean.
- Checked by eye at the dev URL: the public site renders light with the brand-green step indicator and every Bangla label legible.

---

## Phase 0 — Foundation · 2026-09-17

**Status: complete**

### Added

- **Packages** — the four approved: `spatie/laravel-permission`, `bacon/bacon-qr-code`, `intervention/image`, `barryvdh/laravel-dompdf`.
- **Schema** — 11 grouped migrations, 61 tables, portability rules applied throughout.
- **Models** — 46 enums with locale-resolved labels, 45 models with typed relations, explicit fillable lists and `@property` docblocks generated from the real schema, 45 factories.
- **Concerns** — `HasUlid`, `Translatable`, `Auditable`.
- **Services** — settings (cached, grouped), media (re-encoding, variants, SVG sanitisation), SMS (contract + BulkSMSBD + log driver + manager), profile-completion scoring.
- **Observers** — `MemberObserver` (search blob, completion score, batch counter, privacy row), `AuditObserver` (changed attributes only, secrets stripped).
- **Localization** — `SetLocale` middleware, locale switch route, translation sharing, `BanglaNumber`, seven language-file pairs with key parity enforced by a test.
- **Frontend** — brand tokens from the real logos, self-hosted Noto Sans Bengali, `useTranslation`, `usePermission`, format helpers, locale switcher.
- **Seeders** — RBAC, settings, batches, school history, Jubilee, reference data, plus production and demo entry points.
- **Commands** — `make:admin`, `demo:purge`, `sms:balance`, `sms:test`.

### Bugs found and fixed during the phase

| Bug                                              | Why it mattered                                                                                                                                                                                                   |
| ------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Font `subsets` defaulted to `['latin']`          | Noto Sans Bengali downloaded with a unicode-range of U+0000–00FF only. The font looked installed but the browser would never apply it to a Bengali character — Bangla would fall back to a system font, or boxes. |
| `SettingsSeeder` read `env()` directly           | `env()` returns null once `config:cache` has run, which production always does. Seeding a cached install would have written empty organization and school values.                                                 |
| `User` had a null locale in memory               | Column defaults apply on insert, not in Eloquent — so the instance `actingAs()` and post-registration redirects use had no locale, and `SetLocale` crashed on it.                                                 |
| SMS number normalisation dropped the trunk zero  | Produced `881712345678` instead of the `8801712345678` the vendor documents.                                                                                                                                      |
| A Bangla OTP brand sanitised to `"-"`, not empty | Passed the emptiness check and would have produced a malformed OTP.                                                                                                                                               |
| BulkSMSBD code `1032` undocumented               | IP not whitelisted. The send endpoint enforces it, the balance endpoint does not — so a green balance check proves nothing about sending.                                                                         |
| Intervention Image 4.3 API drift                 | `read()` → `decodePath()`, `encodeByExtension()` → `encodeUsingFileExtension()`.                                                                                                                                  |

### Verified, not assumed

- Every model boots; all 143 relations resolve against the real schema; every fillable and cast column exists.
- All 45 factories create a valid row.
- A real seed run produced 47 permissions, 11 roles, 46 batches, 2 milestones, 9 volunteer teams, 6 sponsorship tiers — with the Jubilee holding no date and `dateIsTba()` true.
- Three real SMS delivered through the live gateway (English 1 segment, Bangla 2 segments, OTP 1 segment), confirming the segment-cost model empirically.
- Bangla renders correctly in the browser with conjuncts (ক্ষ, ন্ত, স্ত, র্ণ) forming properly and the specified 1.8 line-height applied.
- **157 tests, 347 assertions. PHPStan level 7, TypeScript and the frontend linter all clean.**

### Corrected

The permission count in the docs was 60; the actual catalogue is 47. The matrix in `docs/04` always listed 47 — the summary figure was an arithmetic error, so the documentation was wrong rather than the code incomplete.

### Not done in this phase

Public, member and admin layouts move to Phase 1: a layout with no pages to render cannot be verified, so building it here would mean committing code nothing exercises.

### Still unverified

The migrations have not been run against MySQL — no server is available in this environment. The portability rules are applied by hand; the CI matrix is what will prove them.

---

## Phase D.1 — Brand assets & verified organizational facts · 2026-09-17

**Status: complete**

The association supplied both official logos, and the school's official site was located. Three contradictions with the original brief were found and resolved before any code was written.

### Corrected

|                      | Brief said                          | Verified                                       | Source                                          |
| -------------------- | ----------------------------------- | ---------------------------------------------- | ----------------------------------------------- |
| School English name  | Govt. Sabuj Shikshyatan High School | **Sabuj Shikshayatan Government High School**  | [sabujsghs.edu.bd](https://sabujsghs.edu.bd)    |
| Association founding | implied 1976                        | **2015**                                       | ribbon on the association logo (স্থাপিত : ২০১৫) |
| Palette              | deep green + gold + maroon          | **green + purple + red**; gold on neither mark | the supplied logos                              |

### Added — verified school data

EIIN **105070** · established **1976** · Hafiz Jute Mills Ltd, Baro Aulia, Sitakunda, Chattogram · 01745950025 · sabujshikha@yahoo.com · Head Teacher Nurjahan Akter · সভাপতি মো: জসিম উদ্দিন · Chattogram education board · https://sabujsghs.edu.bd

### Decisions

| Decision              | Outcome                                                                                                                                                                                        |
| --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| School name           | Keep **সরকারি / Government** — the school was nationalised after the logos were made. Logo artwork is **not** altered; site text uses the current legal name from `settings.school`            |
| "Fifty years" framing | **The school's** fifty years (1976–2026). The association (est. 2015) is credited as **organiser**. Both founding dates seeded as school-history milestones                                    |
| Palette               | Green primary + association purple accent + red alerts, all sampled from the marks. **Gold restricted to the সুবর্ণজয়ন্তী ceremonial pages**, where "golden jubilee" gives it meaning         |
| Settings groups       | `organization` and `school` **split**, so the two bodies' names and founding years can never be conflated. Office-holder names live in settings, not `.env`, so they change without a redeploy |

### Changed

`README.md` · `docs/00-overview.md` (new §3 Verified organizational facts; sections renumbered) · `docs/02-database-schema.md` (settings groups, milestone seed) · `docs/05-modules.md` (settings table) · `docs/07-branding-ui.md` (§1 identity and logos, §2 palette rewritten from the real assets) · `docs/12-environment.md` (split org/school env blocks) · `docs/17-golden-jubilee.md` (new §0 Whose fifty years, hero composition, milestone seed)

### Outstanding

- [ ] **Logo files not yet on disk.** Save the two supplied images to `public/brand/logo-association.png` and `public/brand/logo-school.png`. Hex values in `docs/07` are read from the images and accurate to within a shade; they will be re-sampled exactly once the files land.
- [ ] Favicon, cover, OG and Jubilee banner to be derived from the association mark.
- [ ] School's own history page is empty (_বিস্তারিত আসছে..._), so the founding narrative must come from the committee.

---

## Phase D — Documentation · 2026-09-17

**Status: complete**

Written before any application code, so the design could be reviewed and corrected before a single table existed.

### Added

- `README.md` — project entry point, quick start, principles, doc index, production checklist
- `CHANGELOG.md` — this file
- `docs/00-overview.md` — product scope, Golden Jubilee context, bn/en glossary, scale targets
- `docs/01-architecture.md` — modular monolith, three surfaces, `app/` and `resources/js/` trees, request lifecycle, cross-cutting patterns, SQLite→MySQL portability rules, extension points
- `docs/02-database-schema.md` — 61 tables with every column, type and index; ERD; **documented rationale for each of the six deviations from the original specification's table list**
- `docs/03-routes.md` — every public / member / admin route with name, controller, middleware and rate limit; planned API surface
- `docs/04-roles-permissions.md` — 60 permissions, 11 roles, full matrix, scoped-policy rules, seeding, required tests
- `docs/05-modules.md` — functional spec for all 19 modules
- `docs/06-localization.md` — বাংলা/English architecture, lang file layout, `Translatable` trait, Noto Sans Bengali, Bangla numerals, translator workflow
- `docs/07-branding-ui.md` — color tokens with contrast rules, typography, three visual registers, component inventory, responsive and accessibility requirements
- `docs/08-security-privacy.md` — threat model, privacy field matrix, resource-layer enforcement, upload rules, audit logging, 16 mandatory security tests
- `docs/09-payments.md` — `PaymentGateway` contract, offline recording flow, receipts, gateway-addition checklist
- `docs/10-api.md` — versioned API plan, Sanctum rationale, mobile readiness
- `docs/11-installation.md` — prerequisites, setup, seeders, demo credentials, first-run problems
- `docs/12-environment.md` — every environment variable, dev vs production, secret handling
- `docs/13-deployment.md` — Linux/Nginx/MySQL, queue worker, scheduler, backup, SQLite→MySQL migration, go-live checklist
- `docs/14-testing.md` — strategy, mandatory security tests, per-phase coverage, CI matrix
- `docs/15-roadmap.md` — post-2026 extensibility, and explicitly what _not_ to build
- `docs/16-troubleshooting.md` — Bangla rendering, dompdf limitation, queues, BulkSMSBD error codes, deploy issues
- `docs/17-golden-jubilee.md` — microsite spec, the TBA date rule, event-day operations

### Decisions recorded

| Decision              | Outcome                                                                                                                                                                                                                       |
| --------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Application structure | Modular monolith in **stock Laravel directories**, module-namespaced inside — not a custom `app/Domains/` tree, which would break Wayfinder, `artisan make:*` and Larastan                                                    |
| Production database   | **MySQL 8**; SQLite stays the development default. SQLite serializes writes and would fail at the Golden Jubilee check-in gate                                                                                                |
| Alumni directory      | **Members-only**, public page shows aggregate counts. Admin-togglable via `settings.privacy.public_directory` without a code change                                                                                           |
| Third-party packages  | Four approved: `spatie/laravel-permission`, `bacon/bacon-qr-code`, `intervention/image`, `barryvdh/laravel-dompdf`. Everything else written in-repo                                                                           |
| SMS gateway           | **BulkSMSBD** (`bulksmsbd.net`) as the first concrete `SmsChannel` driver — plain HTTP, no package. `log` driver is the default outside production so no test run can spend real balance                                      |
| Golden Jubilee        | Modelled as a flagship `events` row, **not** a separate subsystem. No date literal anywhere in the codebase                                                                                                                   |
| Schema consolidations | Six documented merges (`member_profiles`, `crm_notes`, `event_attendance`, `invoices`/`receipts`, `notices`, `batch_members`) following the specification's own instruction to avoid redundant tables and duplicated concepts |
| Privacy enforcement   | API Resource layer, server-side. A private field is **absent from the payload**, never blanked or CSS-hidden                                                                                                                  |

### Known risks carried forward

| Risk                                                 | Mitigation                                                                                                                                            |
| ---------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| SQLite dev / MySQL prod drift                        | portable migration rules in `docs/01` §8; CI runs the suite on both drivers                                                                           |
| **Bengali conjunct shaping in dompdf is unreliable** | tested early in the phase that builds receipts; fallback is Latin-script financial documents plus browser print-to-PDF for bilingual member documents |
| Official logo not yet supplied                       | labelled placeholder at `public/brand/`; swapping it is a file replace                                                                                |
| Search ceiling ~50k members                          | `search_blob` + indexed `LIKE` now; Scout + Meilisearch path documented                                                                               |
| Email deliverability at volume                       | requires a real provider plus SPF/DKIM/DMARC — a DNS task, documented not coded                                                                       |
| Queue worker and scheduler required                  | campaigns and exports fail **silently** without them; covered in deployment docs and the go-live checklist                                            |
| Bangla SMS costs ~2.3x Latin                         | Unicode segments are 70 chars vs 160; campaign screen shows segment count and estimated cost before sending                                           |

---

## Upcoming

| Phase                       | Scope                                                                                                                                                 |
| --------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| **7 — CMS**                 | pages · news · announcements · gallery · stories · school history · FAQs · media library                                                              |
| **8 — Reports & hardening** | 11 reports · charts · global search · campaigns (mail + SMS) · notifications · audit viewer · SEO · performance · security sweep · doc reconciliation |
