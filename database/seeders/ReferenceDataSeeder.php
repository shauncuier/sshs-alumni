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
            ['registration', 'Registration', 'নিবন্ধন'],
            ['reception', 'Reception', 'অভ্যর্থনা'],
            ['guest-management', 'Guest Management', 'অতিথি ব্যবস্থাপনা'],
            ['media', 'Media', 'মিডিয়া'],
            ['photography', 'Photography', 'আলোকচিত্র'],
            ['logistics', 'Logistics', 'সরবরাহ ও ব্যবস্থাপনা'],
            ['finance', 'Finance', 'অর্থ'],
            ['technical', 'Technical', 'কারিগরি'],
            ['hospitality', 'Hospitality', 'আপ্যায়ন'],
        ];

        foreach ($teams as $order => [$slug, $name, $nameBn]) {
            VolunteerTeam::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'name_bn' => $nameBn,
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
                    'name_bn' => $nameBn,
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
                'subject_bn' => 'আপনার সদস্যপদের আবেদন আমরা পেয়েছি',
                'body' => "Dear :name,\n\nThank you for applying for membership. The committee will review your application and let you know the outcome.",
                'body_bn' => "প্রিয় :name,\n\nসদস্যপদের জন্য আবেদন করার জন্য ধন্যবাদ। কমিটি আপনার আবেদন পর্যালোচনা করে ফলাফল জানাবে।",
            ],
            [
                'key' => 'member.approved',
                'name' => 'Membership approved',
                'subject' => 'Your membership has been approved',
                'subject_bn' => 'আপনার সদস্যপদ অনুমোদিত হয়েছে',
                'body' => "Dear :name,\n\nYour membership is approved. Your membership number is :membership_no.",
                'body_bn' => "প্রিয় :name,\n\nআপনার সদস্যপদ অনুমোদিত হয়েছে। আপনার সদস্য নম্বর :membership_no।",
            ],
            [
                'key' => 'member.correction_requested',
                'name' => 'Correction requested',
                'subject' => 'We need a correction to your application',
                'subject_bn' => 'আপনার আবেদনে সংশোধন প্রয়োজন',
                'body' => "Dear :name,\n\nPlease update your application: :message",
                'body_bn' => "প্রিয় :name,\n\nঅনুগ্রহ করে আপনার আবেদন সংশোধন করুন: :message",
            ],
            [
                'key' => 'event.registered',
                'name' => 'Event registration confirmed',
                'subject' => 'Your registration is confirmed',
                'subject_bn' => 'আপনার নিবন্ধন নিশ্চিত হয়েছে',
                'body' => "Dear :name,\n\nYour registration for :event is confirmed. Reference: :reference",
                'body_bn' => "প্রিয় :name,\n\n:event-এর জন্য আপনার নিবন্ধন নিশ্চিত হয়েছে। রেফারেন্স: :reference",
            ],
            [
                'key' => 'payment.received',
                'name' => 'Payment received',
                'subject' => 'Payment received',
                'subject_bn' => 'পেমেন্ট গৃহীত হয়েছে',
                'body' => "Dear :name,\n\nWe have received :amount. Receipt number: :receipt_no",
                'body_bn' => "প্রিয় :name,\n\n:amount গৃহীত হয়েছে। রসিদ নম্বর: :receipt_no",
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
     * Privacy policy and terms. Marked is_system so they cannot be deleted —
     * every public site needs them, and a deleted privacy policy is a legal
     * problem rather than a content one.
     */
    private function systemPages(): void
    {
        $pages = [
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'title_bn' => 'গোপনীয়তা নীতি',
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms & Conditions',
                'title_bn' => 'শর্তাবলী',
            ],
        ];

        foreach ($pages as $page) {
            Page::query()->firstOrCreate(
                ['slug' => $page['slug']],
                [
                    ...$page,
                    'body' => '',
                    'is_system' => true,
                    'status' => ContentStatus::Draft,
                ],
            );
        }
    }
}
