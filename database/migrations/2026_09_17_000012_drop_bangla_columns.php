<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The platform is English-only.
 *
 * The original design paired every user-visible text column with a `_bn` twin
 * resolved by locale. The committee decided the site reads in English, so the
 * pairs are removed rather than left as 43 columns nothing writes to and
 * nothing reads.
 *
 * The Bangla that SURVIVES is the organisation's and the school's own names
 * and the Golden Jubilee's title and theme line. Those are identity, not
 * translation, and they live in the `settings` table as ordinary values —
 * `organization.name_bn`, `school.name_bn`, `jubilee.title_bn`,
 * `jubilee.theme_bn`. They are untouched by this migration.
 *
 * This drops columns and the data in them. It is written as a forward
 * migration rather than an edit to the original files so an existing database
 * does not have to be rebuilt; `down()` restores the columns but cannot
 * restore their contents.
 *
 * @see docs/06-localization.md
 */
return new class extends Migration
{
    /**
     * Every dropped column, by table.
     *
     * @var array<string, array<int, string>>
     */
    private const COLUMNS = [
        'alumni_stories' => ['title_bn', 'body_bn'],
        'announcements' => ['title_bn', 'body_bn'],
        'batches' => ['name_bn', 'description_bn'],
        'campaigns' => ['subject_bn', 'body_bn'],
        'committee_members' => ['name_bn', 'designation_bn', 'bio_bn'],
        'committees' => ['name_bn', 'description_bn'],
        'crm_contacts' => ['name_bn'],
        'crm_tags' => ['name_bn'],
        'donations' => ['message_bn'],
        'event_ticket_types' => ['name_bn'],
        'events' => ['title_bn', 'summary_bn', 'description_bn', 'venue_bn'],
        'faqs' => ['question_bn', 'answer_bn'],
        'gallery_albums' => ['title_bn', 'description_bn'],
        'gallery_images' => ['caption_bn'],
        'media' => ['alt_bn'],
        'members' => ['full_name_bn', 'bio_bn'],
        'message_templates' => ['subject_bn', 'body_bn'],
        'news' => ['title_bn', 'excerpt_bn', 'body_bn'],
        'pages' => ['title_bn', 'body_bn'],
        'school_milestones' => ['title_bn', 'description_bn'],
        'sponsors' => ['name_bn'],
        'sponsorship_packages' => ['name_bn', 'benefits_bn'],
        'volunteer_teams' => ['name_bn', 'description_bn'],
        // Not a `_bn` twin, but the same decision: a per-user language
        // preference with one language to choose from is a control that can
        // only ever be set to its own default.
        'users' => ['locale'],
    ];

    /**
     * The columns that were TEXT rather than VARCHAR, so `down()` restores the
     * right type instead of silently truncating long bodies.
     *
     * @var array<int, string>
     */
    private const TEXT_COLUMNS = [
        'answer_bn', 'benefits_bn', 'bio_bn', 'body_bn', 'description_bn', 'message_bn',
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $present = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn($table, $column),
            ));

            if ($present === []) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($present): void {
                $blueprint->dropColumn($present);
            });
        }
    }

    /**
     * Restores the columns, empty. The Bangla text they held is not recoverable
     * from here — it would have to come from a backup.
     */
    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns): void {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        continue;
                    }

                    if ($table === 'users' && $column === 'locale') {
                        $blueprint->string('locale', 5)->default('en')->after('email');

                        continue;
                    }

                    if (in_array($column, self::TEXT_COLUMNS, true)) {
                        $blueprint->text($column)->nullable();

                        continue;
                    }

                    $blueprint->string($column, 255)->nullable();
                }
            });
        }
    }
};
