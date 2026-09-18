<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CampaignChannel;
use App\Enums\ContentStatus;
use App\Enums\SponsorTier;
use App\Models\MessageTemplate;
use App\Models\Page;
use App\Models\SponsorshipPackage;
use App\Models\VolunteerTeam;
use Illuminate\Database\Seeder;

/**
 * Reference data the platform needs to function: volunteer teams, sponsorship
 * tiers, notification templates and the two system pages.
 *
 * All idempotent.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->volunteerTeams();
        $this->sponsorshipPackages();
        $this->messageTemplates();
        $this->systemPages();
    }

    /**
     * The nine standard teams from the specification.
     */
    private function volunteerTeams(): void
    {
        $teams = [
            ['registration', 'Registration'],
            ['reception', 'Reception'],
            ['guest-management', 'Guest Management'],
            ['media', 'Media'],
            ['photography', 'Photography'],
            ['logistics', 'Logistics'],
            ['finance', 'Finance'],
            ['technical', 'Technical'],
            ['hospitality', 'Hospitality'],
        ];

        foreach ($teams as $order => [$slug, $name]) {
            VolunteerTeam::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'display_order' => $order + 1,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * Sponsorship tiers. Amounts are left null for the committee to set —
     * seeding a guess would look authoritative and be wrong.
     */
    private function sponsorshipPackages(): void
    {
        $packages = [
            [SponsorTier::Title, 'Title Sponsor', 'প্রধান পৃষ্ঠপোষক'],
            [SponsorTier::Platinum, 'Platinum', 'প্ল্যাটিনাম'],
            [SponsorTier::Gold, 'Gold', 'গোল্ড'],
            [SponsorTier::Silver, 'Silver', 'সিলভার'],
            [SponsorTier::Partner, 'Partner', 'পার্টনার'],
            [SponsorTier::Custom, 'Custom', 'কাস্টম'],
        ];

        foreach ($packages as $order => [$tier, $name, $nameBn]) {
            SponsorshipPackage::query()->firstOrCreate(
                ['slug' => $tier->value],
                [
                    'name' => $name,
                    'tier' => $tier,
                    'currency' => 'BDT',
                    'display_order' => $order + 1,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * System notification templates, bilingual.
     *
     * Marked is_system so the admin UI can prevent deletion — the application
     * dispatches these by key.
     */
    private function messageTemplates(): void
    {
        $templates = [
            [
                'key' => 'member.registered',
                'name' => 'Membership application received',
                'subject' => 'We have received your membership application',
                'body' => "Dear :name,\n\nThank you for applying for membership. The committee will review your application and let you know the outcome.",
            ],
            [
                'key' => 'member.approved',
                'name' => 'Membership approved',
                'subject' => 'Your membership has been approved',
                'body' => "Dear :name,\n\nYour membership is approved. Your membership number is :membership_no.",
            ],
            [
                'key' => 'member.correction_requested',
                'name' => 'Correction requested',
                'subject' => 'We need a correction to your application',
                'body' => "Dear :name,\n\nPlease update your application: :message",
            ],
            [
                'key' => 'event.registered',
                'name' => 'Event registration confirmed',
                'subject' => 'Your registration is confirmed',
                'body' => "Dear :name,\n\nYour registration for :event is confirmed. Reference: :reference",
            ],
            [
                'key' => 'payment.received',
                'name' => 'Payment received',
                'subject' => 'Payment received',
                'body' => "Dear :name,\n\nWe have received :amount. Receipt number: :receipt_no",
            ],
        ];

        foreach ($templates as $template) {
            MessageTemplate::query()->firstOrCreate(
                ['key' => $template['key']],
                [
                    ...$template,
                    'channel' => CampaignChannel::Mail,
                    'is_system' => true,
                ],
            );
        }
    }

    /**
     * Privacy policy and terms.
     *
     * Marked `is_system` so they cannot be deleted — every public site needs
     * them, and a deleted privacy policy is a legal problem rather than a
     * content one.
     *
     * BOTH ARE SEEDED AS DRAFTS, and they stay drafts until a person publishes
     * them. The text below describes what this platform actually collects and
     * does, which is a factual matter the code can speak to; whether it is
     * adequate under Bangladeshi law, and what the association promises on top
     * of it, is not. Somebody has to read these before they go live.
     *
     * `firstOrCreate` means editing them in the admin panel is safe: a later
     * re-seed will not overwrite what the committee wrote.
     */
    private function systemPages(): void
    {
        $pages = [
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'body' => $this->privacyPolicyDraft(),
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms & Conditions',
                'body' => $this->termsDraft(),
            ],
        ];

        foreach ($pages as $page) {
            Page::query()->firstOrCreate(
                ['slug' => $page['slug']],
                [
                    ...$page,
                    'is_system' => true,
                    'status' => ContentStatus::Draft,
                ],
            );
        }
    }

    /**
     * A starting draft describing what the platform does, for the committee to
     * check and complete. NOT legal advice, and deliberately not published.
     */
    private function privacyPolicyDraft(): string
    {
        return <<<'TEXT'
        DRAFT — not yet published. The committee should read this through,
        correct anything that does not match how the association actually
        works, and take advice on whether it is sufficient before publishing.

        What we collect

        When you apply for membership we ask for your name, your SSC year and
        batch, your contact details, and optionally your occupation, employer,
        location, date of birth and blood group. You choose how much of this
        other members can see; the settings are on your profile page and can be
        changed at any time.

        We store a record of your membership status, any membership fees or
        donations recorded against your name, the events you register for, and
        anything you post in the members' community area.

        What other members can see

        The alumni directory is open to verified members only, never to the
        public. Within it, each field is shown only if you have said it may be.
        Your address, date of birth, student ID and emergency contact are never
        shown to other members at all — they exist for the association's
        administrative use.

        What the public can see

        The public site shows aggregate counts, the names and photographs of
        committee members, and anything you choose to publish — an alumni story
        you submit, or a donation you ask to be listed on the donor wall. A
        donation marked anonymous is never listed.

        Scanning the QR code on a membership card shows the holder's name,
        batch, membership number and status, and nothing else.

        Photographs

        Photographs you upload are re-encoded when they are stored, which
        removes the location data phones record inside image files.

        Who else sees your data

        Nobody outside the association. We do not sell or share member data.
        Email and SMS are sent through service providers who handle the message
        in transit.

        How long we keep it

        Membership records are kept for as long as the association exists; that
        is what an alumni register is. Payment records are kept because the
        association has to account for its money. You may ask for your profile
        to be hidden or your membership archived at any time.

        Asking us about your data

        Write to the association using the contact page. A person reads it.
        TEXT;
    }

    private function termsDraft(): string
    {
        return <<<'TEXT'
        DRAFT — not yet published. The committee should read this through and
        take advice before publishing.

        Membership

        Membership is open to former students, former and current teachers, and
        staff of the school. Applications are reviewed by the committee, which
        may ask for confirmation of your batch before approving one.

        Your account

        Keep your password to yourself. Tell us if you think somebody else has
        used your account.

        The community area

        The members' area is for alumni talking to alumni. Do not post anything
        abusive, dishonest or unlawful, and do not post other people's personal
        information. Moderators may hide or remove a post, and will record why.
        They do not edit what you wrote.

        Payments

        Membership fees, donations and event payments are recorded by the
        association when they are received. Receipts are issued for every
        payment. This site does not take card payments.

        Content you submit

        An alumni story or photograph you submit may be published on the public
        site once the committee has read it. You keep whatever rights you have
        in it; you are giving the association permission to publish it. Ask us
        and we will take it down.

        Changes

        These terms may change. The date of the last change is shown on this
        page.
        TEXT;
    }
}
