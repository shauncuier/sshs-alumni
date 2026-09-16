<?php

declare(strict_types=1);

use App\Enums\AnnouncementKind;
use App\Enums\AnnouncementLevel;
use App\Enums\AudienceScope;
use App\Enums\ContentStatus;
use App\Enums\FaqGroup;
use App\Enums\StoryStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The lightweight CMS. Every authored entity carries a `_bn` twin resolved by
 * the Translatable trait, and SEO fields where it is publicly routed.
 *
 * `notices` is not a separate table: it is `announcements.kind`, because the
 * two have an identical shape, admin UI and public rendering.
 *
 * @see docs/02-database-schema.md sections 1.5 and 12
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 200);
            $table->string('title_bn', 200)->nullable();
            $table->longText('body')->nullable();
            $table->longText('body_bn')->nullable();
            $table->string('status', 16)->default(ContentStatus::Draft->value);
            // Privacy policy and terms cannot be deleted.
            $table->boolean('is_system')->default(false);
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('og_image_path', 255)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'pages_status_index');
        });

        Schema::create('news', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 150)->unique();
            $table->string('title', 200);
            $table->string('title_bn', 200)->nullable();
            $table->string('excerpt', 500)->nullable();
            $table->string('excerpt_bn', 500)->nullable();
            $table->longText('body');
            $table->longText('body_bn')->nullable();
            $table->string('cover_path', 255)->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 60)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status', 16)->default(ContentStatus::Draft->value);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('og_image_path', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'news_status_index');
            $table->index('category', 'news_category_index');
            $table->index('is_featured', 'news_is_featured_index');
            $table->index(['status', 'published_at'], 'news_status_published_index');
        });

        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 16)->default(AnnouncementKind::Announcement->value);
            $table->string('title', 200);
            $table->string('title_bn', 200)->nullable();
            $table->text('body');
            $table->text('body_bn')->nullable();
            $table->string('level', 16)->default(AnnouncementLevel::Info->value);
            $table->string('audience', 16)->default(AudienceScope::Public->value);
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->string('attachment_path', 255)->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 16)->default(ContentStatus::Draft->value);
            $table->timestamps();
            $table->softDeletes();

            $table->index('kind', 'announcements_kind_index');
            $table->index('level', 'announcements_level_index');
            $table->index('audience', 'announcements_audience_index');
            $table->index('starts_at', 'announcements_starts_at_index');
            $table->index('ends_at', 'announcements_ends_at_index');
            $table->index(['status', 'audience'], 'announcements_status_audience_index');
        });

        Schema::create('gallery_albums', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 150)->unique();
            $table->string('title', 200);
            $table->string('title_bn', 200)->nullable();
            $table->text('description')->nullable();
            $table->text('description_bn')->nullable();
            $table->string('cover_path', 255)->nullable();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default(ContentStatus::Draft->value);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('images_count')->default(0);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'gallery_albums_status_index');
        });

        Schema::create('gallery_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gallery_album_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('caption', 255)->nullable();
            $table->string('caption_bn', 255)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['gallery_album_id', 'display_order'], 'gallery_images_order_index');
        });

        Schema::create('alumni_stories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 150)->unique();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name', 150);
            $table->string('title', 200);
            $table->string('title_bn', 200)->nullable();
            $table->longText('body');
            $table->longText('body_bn')->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('career_summary', 255)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status', 16)->default(StoryStatus::Pending->value);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('og_image_path', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'alumni_stories_status_index');
            $table->index('is_featured', 'alumni_stories_is_featured_index');
        });

        // Seeded with 1976 (school founded) and 2015 (association founded).
        // The fifty years belong to the school; the timeline shows where the
        // association joins that story.
        Schema::create('school_milestones', function (Blueprint $table): void {
            $table->id();
            $table->smallInteger('year');
            $table->string('date_label', 60)->nullable();
            $table->string('title', 200);
            $table->string('title_bn', 200)->nullable();
            $table->text('description')->nullable();
            $table->text('description_bn')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_highlighted')->default(false);
            $table->timestamps();

            $table->index('year', 'school_milestones_year_index');
            $table->index('display_order', 'school_milestones_order_index');
        });

        Schema::create('faqs', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 40)->default(FaqGroup::General->value);
            $table->string('question', 300);
            $table->string('question_bn', 300)->nullable();
            $table->text('answer');
            $table->text('answer_bn')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['group', 'display_order'], 'faqs_group_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('school_milestones');
        Schema::dropIfExists('alumni_stories');
        Schema::dropIfExists('gallery_images');
        Schema::dropIfExists('gallery_albums');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('news');
        Schema::dropIfExists('pages');
    }
};
