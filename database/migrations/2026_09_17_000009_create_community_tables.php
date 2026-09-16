<?php

declare(strict_types=1);

use App\Enums\CommentStatus;
use App\Enums\PostStatus;
use App\Enums\ReactionType;
use App\Enums\ReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The lightweight community layer: posts, comments, reactions, reports.
 *
 * Mentions are deliberately NOT a table. A mention is stored inline in the
 * post body as `@member:{ulid}` and resolved at render time — a join table
 * would add write cost for something only ever read with the post.
 *
 * @see docs/02-database-schema.md section 11
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('author_member_id')->constrained('members')->cascadeOnDelete();
            $table->string('category', 40);
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200)->nullable();
            $table->text('body');
            $table->string('status', 16)->default(PostStatus::Published->value);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('comments_enabled')->default(true);
            // Counter caches maintained by observers.
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('reactions_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category', 'posts_category_index');
            $table->index('status', 'posts_status_index');
            $table->index('last_activity_at', 'posts_last_activity_index');
            $table->index(
                ['status', 'category', 'last_activity_at'],
                'posts_feed_index',
            );
        });

        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->morphs('commentable');
            $table->foreignId('author_member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            $table->string('status', 16)->default(CommentStatus::Published->value);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'comments_status_index');
        });

        Schema::create('reactions', function (Blueprint $table): void {
            $table->id();
            $table->morphs('reactable');
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default(ReactionType::Like->value);
            $table->timestamp('created_at')->nullable();

            // One reaction per member per item.
            $table->unique(
                ['member_id', 'reactable_type', 'reactable_id'],
                'reactions_member_reactable_unique',
            );
        });

        Schema::create('content_reports', function (Blueprint $table): void {
            $table->id();
            $table->morphs('reportable');
            $table->foreignId('reporter_member_id')->nullable()
                ->constrained('members')->nullOnDelete();
            $table->string('reason', 40);
            $table->text('note')->nullable();
            $table->string('status', 16)->default(ReportStatus::Open->value);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index('reason', 'content_reports_reason_index');
            $table->index('status', 'content_reports_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reports');
        Schema::dropIfExists('reactions');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('posts');
    }
};
