<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Seeder;

/**
 * Default settings.
 *
 * `organization` (the association, established 2015) and `school`
 * (established 1976) are SEPARATE GROUPS on purpose. The fifty years being
 * celebrated are the school's; the association is the organiser. Conflating
 * them would misstate the history of both bodies.
 *
 * Office-holder names live here rather than in .env so they can change without
 * a redeploy — chairs and head teachers change.
 *
 * Idempotent: existing values are never overwritten, so re-seeding a live
 * database cannot undo an administrator's edits.
 *
 * @see docs/00-overview.md section 3
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->defaults() as $group => $entries) {
            foreach ($entries as $key => [$value, $isPublic]) {
                Setting::query()->firstOrCreate(
                    ['group' => $group, 'key' => $key],
                    ['value' => $value, 'is_public' => $isPublic],
                );
            }
        }

        app(SettingsService::class)->flush();
    }

    /**
     * Each entry is [value, isPublic]. Only public settings reach the
     * frontend; everything else stays server-side.
     *
     * @return array<string, array<string, array{0: mixed, 1: bool}>>
     */
    private function defaults(): array
    {
        return [
            // ── The association — organiser of the Jubilee ───────────────
            'organization' => [
                'name_bn' => [config('organization.association.name_bn'), true],
                'name_en' => [config('organization.association.name_en'), true],
                'short_name' => [config('organization.association.short_name'), true],
                'established' => [(int) config('organization.association.established'), true],
                'logo_path' => ['brand/logo-association.png', true],
                'favicon_path' => ['brand/favicon.svg', true],
                'cover_path' => ['brand/cover.jpg', true],
                'og_image_path' => ['brand/og.jpg', true],
            ],

            // ── The school — whose fifty years are celebrated ────────────
            'school' => [
                'name_bn' => [config('organization.school.name_bn'), true],
                'name_en' => [config('organization.school.name_en'), true],
                'established' => [(int) config('organization.school.established'), true],
                'eiin' => [config('organization.school.eiin'), true],
                'address' => [config('organization.school.address'), true],
                'phone' => [config('organization.school.phone'), true],
                'email' => [config('organization.school.email'), true],
                'website' => [config('organization.school.website'), true],
                'board_bn' => [config('organization.school.board_bn'), true],
                'logo_path' => ['brand/logo-school.png', true],
                // Office holders change; these are edited in the admin panel.
                'head_teacher' => [config('organization.school.head_teacher'), true],
                'chairperson_bn' => [config('organization.school.chairperson_bn'), true],
                'motto_bn' => [config('organization.school.motto_bn'), true],
            ],

            'contact' => [
                'email' => [config('organization.association.contact_email'), true],
                'phone' => [config('organization.association.contact_phone'), true],
                'address' => ['', true],
                'map_url' => ['', true],
            ],

            'social' => [
                'facebook' => ['', true],
                'youtube' => ['', true],
                'linkedin' => ['', true],
                'website' => ['', true],
            ],

            // ── Golden Jubilee microsite copy ────────────────────────────
            // NOTE: no date. The committee has not fixed one, and it is
            // published from /admin/jubilee onto the flagship event row.
            'jubilee' => [
                'title_bn' => ['সুবর্ণজয়ন্তী ২০২৬', true],
                'title_en' => ['Golden Jubilee 2026', true],
                'theme_bn' => ['৫০ বছরের গৌরবময় পথচলা', true],
                'theme_en' => ['Fifty Glorious Years', true],
                'from_year' => [1976, true],
                'to_year' => [2026, true],
                'hero_image' => ['brand/jubilee-banner.jpg', true],
                // Still requires the event's date_status to be `announced`.
                'show_countdown' => [true, true],
                'organizer_block_bn' => ['আয়োজনে: প্রাক্তন ছাত্র-ছাত্রী পরিষদ', true],
                'registration_note_bn' => ['', true],
            ],

            'registration' => [
                'open' => [true, true],
                'require_approval' => [true, false],
                'duplicate_warning' => [true, false],
            ],

            'membership' => [
                'number_prefix' => ['SSHS', false],
                'annual_fee' => [0, true],
                'life_fee' => [0, true],
            ],

            'event' => [
                'default_currency' => ['BDT', true],
            ],

            'privacy' => [
                // Members-only directory. Flipping this opens the public
                // directory without a code change; the resource layer already
                // handles an anonymous viewer.
                'public_directory' => [false, true],
                'default_show_phone' => [false, false],
                'default_show_email' => [false, false],
            ],

            'seo' => [
                'meta_title' => ['প্রাক্তন ছাত্র-ছাত্রী পরিষদ — সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয়', true],
                'meta_description' => ['সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয়ের প্রাক্তন ছাত্র-ছাত্রীদের সংগঠন। সুবর্ণজয়ন্তী ২০২৬।', true],
            ],

            'notification' => [
                'notify_admins_on_registration' => [true, false],
                'notify_member_on_approval' => [true, false],
            ],

            'system' => [
                'default_language' => [config('app.locale', 'bn'), true],
                'timezone' => [config('app.timezone', 'Asia/Dhaka'), false],
                'footer_note_bn' => ['', true],
            ],
        ];
    }
}
