<?php

declare(strict_types=1);

use App\Enums\PipelineStage;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CRM. `crm_contacts` covers every person the association deals with who is
 * not (yet) an alumni record: prospects, donors, sponsors, guests, partners.
 *
 * When a contact turns out to be an alumnus, `member_id` links them — the
 * person is never duplicated.
 *
 * `crm_activities` is one polymorphic timeline over members AND contacts,
 * which is what lets a member have an activity history without a shadow
 * contact row. The brief listed crm_notes separately; notes, calls, emails and
 * meetings share an identical shape, so they are one table with a `type`.
 *
 * @see docs/02-database-schema.md sections 1.2 and 7
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_contacts', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 24);
            $table->string('name', 150);
            $table->string('name_bn', 150)->nullable();
            $table->string('organization_name', 150)->nullable();
            $table->string('designation', 120)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('whatsapp', 32)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('district', 80)->nullable();
            $table->string('country', 80)->nullable();
            $table->string('source', 80)->nullable();
            $table->string('relationship_type', 80)->nullable();
            $table->string('pipeline_status', 24)->default(PipelineStage::New->value);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_activity_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type', 'crm_contacts_type_index');
            $table->index('email', 'crm_contacts_email_index');
            $table->index('phone', 'crm_contacts_phone_index');
            $table->index('source', 'crm_contacts_source_index');
            $table->index('organization_name', 'crm_contacts_organization_index');
            $table->index('last_activity_at', 'crm_contacts_last_activity_index');
            $table->index(['pipeline_status', 'owner_id'], 'crm_contacts_pipeline_owner_index');
        });

        // ── Activity timeline ────────────────────────────────────────────
        // `system` rows are written automatically by observers on
        // verification, payment and registration events, which is what makes
        // the timeline in docs/05-modules.md section 6 a single ordered query.
        Schema::create('crm_activities', function (Blueprint $table): void {
            $table->id();
            $table->morphs('subject');
            $table->string('type', 16);
            $table->string('subject_line', 200)->nullable();
            $table->text('body')->nullable();
            $table->string('outcome', 80)->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('type', 'crm_activities_type_index');
            $table->index('occurred_at', 'crm_activities_occurred_at_index');
            $table->index(
                ['subject_type', 'subject_id', 'occurred_at'],
                'crm_activities_subject_occurred_index',
            );
        });

        Schema::create('crm_tasks', function (Blueprint $table): void {
            $table->id();
            $table->nullableMorphs('subject');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('status', 16)->default(TaskStatus::Open->value);
            $table->string('priority', 12)->default(TaskPriority::Normal->value);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('due_at', 'crm_tasks_due_at_index');
            $table->index(['status', 'assigned_to'], 'crm_tasks_status_assigned_index');
        });

        Schema::create('crm_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('name_bn', 60)->nullable();
            $table->string('color', 16)->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        // Polymorphic so a tag applies to members and contacts alike.
        Schema::create('crm_taggables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');
            $table->timestamps();

            $table->unique(
                ['crm_tag_id', 'taggable_type', 'taggable_id'],
                'crm_taggables_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_taggables');
        Schema::dropIfExists('crm_tags');
        Schema::dropIfExists('crm_tasks');
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('crm_contacts');
    }
};
