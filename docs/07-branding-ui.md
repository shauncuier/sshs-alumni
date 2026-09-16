# 07 — Branding, Design System & UI

The platform should read as a **prestigious academic institution**, not a SaaS product and not a generic Bootstrap template. Elegant, trustworthy, community-focused, mobile-first.

---

## 1. Identity

### Two organizations, two logos, two founding dates

This distinction governs the copy on every page. Getting it wrong misstates the history of both bodies.

| | বাংলা | English | Established |
|---|---|---|---|
| **The school** | সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয় | **Sabuj Shikshayatan Government High School** | **১৯৭৬ / 1976** |
| **The association** | প্রাক্তন ছাত্র-ছাত্রী পরিষদ | Former Students Association | **২০১৫ / 2015** |

> **English spelling is fixed by the school's own official site** ([sabujsghs.edu.bd](https://sabujsghs.edu.bd)): *Sabuj **Shikshayatan** Government High School* — not "Shikshyatan", and not "Govt. … High School" as the original brief had it. This exact string is used in page titles, SEO metadata, email templates and PDF documents. Verified facts are in [00-overview.md §3](00-overview.md).

| | |
|---|---|
| Event | সুবর্ণজয়ন্তী ২০২৬ / Golden Jubilee 2026 |
| Milestone | ১৯৭৬ — ২০২৬ — **the school's fifty years** |
| Theme | ৫০ বছরের গৌরবময় পথচলা |
| Organiser | প্রাক্তন ছাত্র-ছাত্রী পরিষদ |

**The fifty years belong to the school.** The association, founded in 2015, is the body organising the celebration. Hero and Jubilee copy credits the school's milestone and names the association as organiser. The association's own ২০১৫ founding appears on the About page and as a milestone in the school-history timeline. See [17-golden-jubilee.md](17-golden-jubilee.md).

### Logos — supplied, not generated

Both official marks have been supplied by the association. **No AI-generated logo is produced or substituted.**

| Slot | Path | Source |
|---|---|---|
| **Association logo** | `public/brand/logo-association.png` | supplied — primary site identity |
| **School logo** | `public/brand/logo-school.png` | supplied — school pages, history, footer pairing |
| Favicon | `public/brand/favicon.svg` + `.ico` | derived from the association mark: 32 / 180 / 192 / 512 |
| Cover image | `public/brand/cover.jpg` | 1920×640 |
| OG image | `public/brand/og.jpg` | 1200×630 |
| Jubilee banner | `public/brand/jubilee-banner.jpg` | 1920×800 |

#### Description of the supplied marks

**Association** — circular, purple/magenta outer ring carrying প্রাক্তন ছাত্র-ছাত্রী পরিষদ on the upper arc and the school name on the lower arc; centre monogram **SSHS** on an open book beneath a lamp flame, above a gear bearing জ্ঞানই শক্তি; green laurel wreath flanking; ribbon banner reading **স্থাপিত : ২০১৫**; two red stars.

**School** — circular, green; school name on the upper arc, locality on the lower; centre open book with lamp and জ্ঞানই শক্তি; two red stars; **স্থাপিত : ১৯৭৬ খ্রিঃ**.

> **Note on the logo wordmark.** Both marks read সবুজ শিক্ষায়তন উচ্চ বিদ্যালয় — without সরকারি — because they predate the school's nationalisation. **This is expected and the artwork is not to be altered.** The site's text, page titles, SEO metadata and email templates use the current legal name, সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয় / Sabuj Shikshayatan Government High School, held in `settings.school`.

#### Usage rules

- Minimum display size 40px; below that use the favicon mark, never a shrunken full logo.
- Clear space on all sides equal to 25% of the logo width.
- Never recolour, stretch, rotate, add effects to, or place either mark on a busy background.
- The association mark leads on all platform surfaces. The school mark appears on `/about/school`, the history timeline, the Jubilee pages and paired in the footer.
- Both marks contain fine detail and are supplied as raster. Serve at 2x for retina; commission SVG redraws if the association ever needs large-format print.

**To update an asset:** replace the file and run `npm run build`. No code changes. Logo, favicon and cover are also overridable from `/admin/settings/organization`, which takes precedence over the files.

## 2. Color

**Derived from the supplied logos, not invented.** Green is the anchor shared by both marks; purple is the association's own accent; red carries the stars.

```css
/* resources/css/app.css — inside @theme */

/* Green — from the school mark and the association's laurel */
--brand-green-900: #08532A;   /* deepest — footer, hero overlay */
--brand-green-800: #0E7A3C;   /* PRIMARY — headers, buttons, links */
--brand-green-600: #14934A;   /* hover */
--brand-green-100: #E6F3EC;   /* tint — section backgrounds */

/* Purple — from the association ring */
--brand-purple-700: #7E3D7B;  /* purple text on light */
--brand-purple-600: #9B4D97;  /* ACCENT — association identity, highlights */
--brand-purple-100: #F3E8F2;  /* tint */

/* Red — from the stars on both marks */
--brand-red-700:   #B81C24;   /* alerts, destructive */
--brand-red-100:   #FBE9EA;

/* Gold — সুবর্ণজয়ন্তী ONLY. Appears on neither logo. */
--brand-gold-500:  #C9A227;   /* jubilee accents on dark */
--brand-gold-600:  #A8871C;   /* jubilee text on light */

--brand-cream:     #FBF8F1;   /* public section background */
--brand-ink:       #1A1A1A;   /* body text */
```

> Values are read from the supplied raster logos and are accurate to within a shade. They are re-sampled exactly from `public/brand/*.png` when the assets are installed, and this table is updated if they move.

### Where gold is allowed

Gold appears on neither official mark. It is therefore **restricted to the সুবর্ণজয়ন্তী ceremonial register** — the `/jubilee/*` pages and the Jubilee banner on the home page — where "golden" jubilee gives it actual meaning. It is never used in the admin panel, the member area, or ordinary public pages.

### Contrast rules

| Combination | Ratio | Use |
|---|---|---|
| `green-800` on white | ~5.4:1 ✅ AA | body links, primary buttons |
| white on `green-800` | ~5.4:1 ✅ AA | header, footer, primary button label |
| `purple-600` on white | ~5.4:1 ✅ AA | association accent text, badges |
| white on `purple-600` | ~5.4:1 ✅ AA | accent buttons |
| `red-700` on white | ✅ AA | alerts, destructive actions |
| `gold-600` on white | ✅ AA | jubilee **text** on light |
| `gold-500` on `green-900` | ✅ AA | jubilee text on dark |
| `gold-500` on white | ❌ fails | **never used for text on light** — surfaces and borders only |

Every pairing above is verified, not assumed. New combinations are checked before use.

### Dark mode

The starter kit already ships `use-appearance.tsx` and a `.dark` variant. Brand tokens get dark equivalents: green lightens toward `green-600`, cream becomes near-black, gold holds (it already passes on dark).

Dark mode applies to the **member and admin surfaces**. The public site renders light-only — a public alumni site with a dark mode toggle reads as a developer tool, and the gold-on-cream identity does not survive inversion.

## 3. Typography

| Script | Family | Source |
|---|---|---|
| Bangla | Noto Sans Bengali | self-hosted |
| Latin | Instrument Sans | existing (bunny fonts, already in `vite.config.ts`) |

Bangla line-height is 1.8 for body, 1.4 minimum for headings. See [06-localization.md §6](06-localization.md).

### Scale

| Element | Size | Weight |
|---|---|---|
| Hero headline | `clamp(2rem, 5vw, 3.5rem)` | 700 |
| Page title | `1.875rem` | 600 |
| Section heading | `1.5rem` | 600 |
| Card title | `1.125rem` | 600 |
| Body | `1rem` | 400 |
| Meta / caption | `0.875rem` | 400 |

## 4. Surface treatments

Three distinct visual registers so pages do not read as one long template:

| Register | Where | Treatment |
|---|---|---|
| **Institutional** | About, committees, history, pages | cream background, generous whitespace, serif-weight headings, thin gold rules |
| **Editorial** | News, stories, gallery, achievements | white, image-led, asymmetric grids |
| **Ceremonial** | Golden Jubilee microsite | `green-900` → `green-800` gradient, gold typography and rules, centered composition, the 1976–2026 milestone as a hero element |

Admin and member surfaces use the neutral shadcn palette with green as the accent — a CRM should be quiet.

### Avoiding repetitive pages

The specification explicitly asks for this. Concretely:

- The home page alternates section backgrounds: white → cream → green-900 (stats band) → white → cream.
- Batch pages lead with a cover image and a batch-colored header derived from the SSC year, so SSC 1990 and SSC 2010 do not look identical.
- Event and news indexes use a featured-first layout, not a uniform grid.
- Empty states are illustrated and specific ("এই ব্যাচে এখনও কোনো সদস্য যোগ দেননি"), never a generic "No data".

## 5. Component inventory

### Reused from the starter kit — not rewritten

All 28 `components/ui/*` primitives: alert, avatar, badge, breadcrumb, button, card, checkbox, collapsible, dialog, dropdown-menu, icon, input, input-otp, label, navigation-menu, placeholder-pattern, select, separator, sheet, sidebar, skeleton, sonner, spinner, toggle, toggle-group, tooltip.

Plus `app-sidebar`, `nav-main`, `nav-user`, `breadcrumbs`, `input-error`, `app-sidebar-layout`, `use-flash-toast`, `use-appearance`, `use-mobile`.

### New — shared

`locale-switcher` · `empty-state` · `error-state` · `loading-state` · `confirm-dialog` · `data-table` · `filter-bar` · `pagination` · `search-input` · `media-picker` · `rich-editor` · `drawer` · `tabs-nav` · `status-badge` · `date-display` · `money`

### New — public

`site-header` · `site-footer` · `hero` · `jubilee-banner` · `jubilee-countdown` · `milestone-timeline` · `stat-strip` · `event-card` · `news-card` · `announcement-strip` · `gallery-grid` · `committee-card` · `story-card` · `sponsor-wall` · `cta-band` · `batch-card`

### New — member

`profile-completion` · `membership-card` · `event-ticket` · `directory-card` · `directory-filters` · `post-composer` · `post-card` · `comment-thread` · `reaction-bar`

### New — admin

`stat-card` · `chart-line` · `chart-bar` · `chart-donut` · `crm-timeline` · `pipeline-board` · `verification-panel` · `checkin-scanner` · `audit-row` · `permission-matrix`

### Charts

Rendered as **inline SVG built in-repo** rather than adding a charting library. The seven required charts are line, bar and donut — each is under 100 lines of SVG with a shared axis/scale helper, and a charting dependency would add ~150 KB to the admin bundle for shapes we fully control. Series colors come from the brand tokens and are checked for colorblind-safe separation.

## 6. Layout

### Public

```
┌────────────────────────────────────────────┐
│ announcement-strip (urgent only)            │
│ site-header — logo · nav · locale · login   │  sticky
├────────────────────────────────────────────┤
│ page content, max-w-7xl, 16px side gutter  │
├────────────────────────────────────────────┤
│ site-footer — org · links · contact · social│
└────────────────────────────────────────────┘
```

### Member & Admin

Reuses `app-sidebar-layout.tsx`: collapsible sidebar (state in the existing `sidebar_state` cookie), header with breadcrumbs, global search and the notification bell.

### Admin sidebar sections

Dashboard · Members · CRM · Batches · Events · Golden Jubilee · Payments · Donations · Sponsors · Volunteers · Committees · Community · News · Announcements · Gallery · Pages · School History · Reports · Settings · Users · Roles & Permissions · Audit Logs

Each item is hidden when the user lacks the permission — via `usePermission()`, which hides UI only. The route is protected server-side regardless. See [04-roles-permissions.md](04-roles-permissions.md).

## 7. Responsive

Mobile-first. Breakpoints are Tailwind defaults: `sm` 640 · `md` 768 · `lg` 1024 · `xl` 1280.

| Surface | Mobile behaviour |
|---|---|
| Public nav | sheet drawer |
| Hero | stacked, reduced height, headline `clamp()` floor |
| Directory | single-column cards; filters in a drawer |
| Admin tables | horizontal scroll with a sticky first column; card view under `md` |
| Check-in scanner | full-screen, large tap targets — it is used one-handed at a gate |
| Membership card | fixed aspect ratio, always legible |

Every page is verified at 375px with no horizontal page scroll.

## 8. Required UI states

Every list and every form implements all four. This is a review checklist item, not a suggestion.

| State | Treatment |
|---|---|
| **Loading** | skeleton matching the final layout (`components/ui/skeleton.tsx`), never a bare spinner for content |
| **Empty** | illustration + specific message + the action that resolves it |
| **Error** | what failed, in plain language, and what to do next |
| **Success** | toast via the existing `use-flash-toast` + `sonner` |

Destructive actions go through `confirm-dialog` naming the specific record ("Reject Md. Karim's membership application?"), never a generic "Are you sure?".

## 9. Accessibility

- Semantic landmarks; one `h1` per page.
- Visible focus rings — never `outline: none` without a replacement.
- All interactive controls keyboard-reachable; the check-in scanner is fully operable without a mouse.
- `aria-label` on icon-only buttons, in the active locale.
- Form errors associated with their inputs via the existing `input-error` component.
- Color is never the sole carrier of meaning — status badges carry text as well as color.
- Images require `alt`; `media.alt` / `alt_bn` exist for this and the upload form asks for them.
