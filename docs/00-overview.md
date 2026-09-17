# 00 — Product Overview

> **প্রাক্তন ছাত্র-ছাত্রী পরিষদ**
> **সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয়**
>
> Former Students Association _(est. 2015)_
> Sabuj Shikshayatan Government High School _(est. 1976 · EIIN 105070)_

---

## 1. What this is

A permanent digital platform for the alumni association of Sabuj Shikshayatan Government High School, Sitakunda, Chattogram.

It is **three connected products in one codebase**:

| Surface                      | Audience          | Purpose                                                   |
| ---------------------------- | ----------------- | --------------------------------------------------------- |
| **Public community website** | Anyone            | Identity, news, events, history, membership intake        |
| **Member area**              | Verified alumni   | Profile, directory, community, events, payments           |
| **Admin CRM**                | Committee & staff | Membership pipeline, CRM, events, money, content, reports |

## 2. What this is _not_

**It is not an event website.**

The first major use of the platform is **সুবর্ণজয়ন্তী ২০২৬ / Golden Jubilee 2026**, but the Jubilee is modelled as _one flagship event row inside a general-purpose event system_. When the Jubilee is over, the platform continues to run reunions, seminars, sports days, cultural programmes, fundraising drives and committee meetings for the next fifty years.

Every design decision in this repository follows from that: nothing about the Golden Jubilee is hard-coded, special-cased, or structurally privileged beyond a boolean flag and a settings group.

## 3. Verified organizational facts

Two distinct bodies with two distinct founding dates. **This distinction governs copy across the whole site.**

### The school

Source: the school's official site, **[sabujsghs.edu.bd](https://sabujsghs.edu.bd)** — verified 2026-09-17.

| Field                          | Value                                                   |
| ------------------------------ | ------------------------------------------------------- |
| Name (English)                 | **Sabuj Shikshayatan Government High School**           |
| Name (বাংলা)                   | সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয়                   |
| **EIIN**                       | **105070**                                              |
| Established                    | **1976**                                                |
| Address                        | Hafiz Jute Mills Ltd, Baro Aulia, Sitakunda, Chattogram |
| Phone                          | 01745950025                                             |
| Email                          | sabujshikha@yahoo.com                                   |
| Head Teacher                   | Nurjahan Akter                                          |
| সভাপতি (Chair, governing body) | মো: জসিম উদ্দিন                                         |
| Education board                | মাধ্যমিক ও উচ্চ মাধ্যমিক শিক্ষা বোর্ড, চট্টগ্রাম        |
| Website                        | https://sabujsghs.edu.bd                                |

> **Spelling.** The official English form is _Sabuj **Shikshayatan** Government High School_. The original brief used "Govt. Sabuj Shikshyatan High School"; the school's own `.edu.bd` site is authoritative and its spelling is used everywhere in this platform.

> **The logos predate nationalisation.** Both supplied marks read সবুজ শিক্ষায়তন উচ্চ বিদ্যালয় — without সরকারি. The artwork is correct and is not to be altered; the _text_ of the site uses the current legal name. See [07-branding-ui.md §1](07-branding-ui.md).

### The association

| Field          | Value                                                    |
| -------------- | -------------------------------------------------------- |
| Name (বাংলা)   | প্রাক্তন ছাত্র-ছাত্রী পরিষদ                              |
| Name (English) | Former Students Association                              |
| Established    | **২০১৫ / 2015** — per the ribbon on the association logo |
| Role           | organiser of সুবর্ণজয়ন্তী ২০২৬                          |

### The consequence for copy

**১৯৭৬ — ২০২৬ is the school's fifty years.** The association, founded in 2015, is the body organising the celebration. Hero and Jubilee copy credits the school's milestone and names the association as organiser; the association's own 2015 founding appears on the About page and as a milestone in the school-history timeline.

Head Teacher and সভাপতি names are seeded as _initial_ settings values and are editable in the admin panel — office holders change, and a redeploy must never be required to update them.

## 4. The Golden Jubilee 2026

| Field        | Value                             |
| ------------ | --------------------------------- |
| Bangla name  | সুবর্ণজয়ন্তী ২০২৬                |
| English name | Golden Jubilee 2026               |
| Occasion     | 50th Anniversary + Alumni Reunion |
| Milestone    | **১৯৭৬ — ২০২৬** / 1976 — 2026     |
| Theme        | **৫০ বছরের গৌরবময় পথচলা**        |
| Date         | **NOT FIXED**                     |

### The date rule

The final event date has **not** been decided by the committee.

Until an administrator publishes it from `/admin/jubilee`, every public surface must render:

> **তারিখ শীঘ্রই ঘোষণা করা হবে**

This is enforced structurally, not by convention:

- `events.starts_at` is **nullable**
- `events.date_status` is an enum: `tba` | `announced`
- The countdown component renders **only** when `date_status === 'announced'`
- A feature test asserts the Bangla TBA string appears while `starts_at` is null

No date literal for the Jubilee exists anywhere in the codebase. See [17-golden-jubilee.md](17-golden-jubilee.md).

## 5. Scale targets

| Dimension                       | Target                |
| ------------------------------- | --------------------- |
| Alumni records                  | 10,000 — 50,000       |
| Concurrent users (normal)       | < 100                 |
| Concurrent users (event day)    | 500 — 2,000           |
| Batches                         | ~45 (SSC 1981 onward) |
| Event check-ins per hour (peak) | ~1,500                |

These numbers drive the MySQL-in-production decision and the queue/caching strategy. See [13-deployment.md](13-deployment.md).

## 6. Core principles

1. **Privacy before features.** A member controls what the world sees. Private fields are never serialized into a response — not hidden with CSS, not filtered on the client. See [08-security-privacy.md](08-security-privacy.md).
2. **Auditability.** Every administrative action that touches a person, money, or published content leaves an audit row.
3. **No user-facing string is hard-coded in a component.** The site reads in English; copy lives in `lang/en/`. See [06-localization.md](06-localization.md).
4. **Normalized, not fragmented.** One concept, one table. Consolidations from the original spec are documented with reasons in [02-database-schema.md](02-database-schema.md).
5. **Server-side authorization.** The client receives a permission list to hide buttons. The server decides. Always.
6. **Extensible, not over-engineered.** Payment gateways, notification channels and search drivers sit behind interfaces. Everything else is plain Laravel.

## 7. Glossary — বাংলা terms and their English equivalents

The platform reads in English. This table is here because the association and the school use these terms among themselves, and the English column is what appears on screen.

| বাংলা                                 | English                                   | In code                       |
| ------------------------------------- | ----------------------------------------- | ----------------------------- |
| প্রাক্তন ছাত্র-ছাত্রী পরিষদ           | Former Students Association               | the _association_ — est. 2015 |
| সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয় | Sabuj Shikshayatan Government High School | the _school_ — est. 1976      |
| সুবর্ণজয়ন্তী                         | Golden Jubilee                            | `jubilee`                     |
| স্থাপিত                               | Established                               | `established_year`            |
| সভাপতি                                | Chair / President                         | `chairperson`                 |
| প্রধান শিক্ষক                         | Head Teacher                              | `head_teacher`                |
| জ্ঞানই শক্তি                          | Knowledge is power                        | school motto, on both logos   |
| ব্যাচ                                 | Batch                                     | `batch` — keyed by SSC year   |
| সদস্য                                 | Member                                    | `member`                      |
| সদস্য নম্বর                           | Membership number                         | `membership_no`               |
| যাচাই                                 | Verification                              | `verification`                |
| কমিটি                                 | Committee                                 | `committee`                   |
| স্বেচ্ছাসেবক                          | Volunteer                                 | `volunteer`                   |
| পৃষ্ঠপোষক                             | Sponsor                                   | `sponsor`                     |
| অনুদান                                | Donation                                  | `donation`                    |
| অনুষ্ঠান                              | Event                                     | `event`                       |
| বিজ্ঞপ্তি                             | Notice / Announcement                     | `announcement`                |
| গ্যালারি                              | Gallery                                   | `gallery`                     |
| তারিখ শীঘ্রই ঘোষণা করা হবে            | Date will be announced soon               | `jubilee.date_tba`            |

### Key domain distinctions

- **User vs Member.** A `User` is a login identity. A `Member` is an alumni record. They are linked 1:1 but either can exist alone — the association can enter a 1985 graduate who will never log in, and a hired office staff member can have a login with no alumni record.
- **Member vs CRM Contact.** A `Member` is the canonical alumni record. A `CrmContact` is any other person the association deals with — a prospect, donor, sponsor, guest, partner. When a contact turns out to be an alumnus, `crm_contacts.member_id` links them instead of duplicating the person. CRM activity attaches polymorphically to both.
- **Batch.** Keyed by **SSC year**, not admission year. `SSC 1990`, `SSC 1995`, `SSC 2000`. The school opened in 1976, so the first batch is roughly SSC 1981.

## 8. Document map

| Doc                                                | Contents                                    |
| -------------------------------------------------- | ------------------------------------------- |
| [01-architecture.md](01-architecture.md)           | Modular monolith, directory trees, patterns |
| [02-database-schema.md](02-database-schema.md)     | All tables, columns, indexes, ERD           |
| [03-routes.md](03-routes.md)                       | Every route, name, middleware               |
| [04-roles-permissions.md](04-roles-permissions.md) | RBAC matrix                                 |
| [05-modules.md](05-modules.md)                     | Functional spec per module                  |
| [06-localization.md](06-localization.md)           | English-only decision, what stayed in বাংলা |
| [07-branding-ui.md](07-branding-ui.md)             | Design system                               |
| [08-security-privacy.md](08-security-privacy.md)   | Threat model, privacy enforcement           |
| [09-payments.md](09-payments.md)                   | Gateway abstraction                         |
| [10-api.md](10-api.md)                             | Future API surface                          |
| [11-installation.md](11-installation.md)           | Local setup                                 |
| [12-environment.md](12-environment.md)             | Env vars                                    |
| [13-deployment.md](13-deployment.md)               | Production                                  |
| [14-testing.md](14-testing.md)                     | Test strategy                               |
| [15-roadmap.md](15-roadmap.md)                     | Post-2026                                   |
| [16-troubleshooting.md](16-troubleshooting.md)     | Common failures                             |
| [17-golden-jubilee.md](17-golden-jubilee.md)       | Jubilee microsite                           |
