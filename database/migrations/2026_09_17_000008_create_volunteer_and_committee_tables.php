<?php

declare(strict_types=1);

use App\Enums\AssignmentStatus;
use App\Enums\CommitteeMemberStatus;
use App\Enums\VolunteerStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Volunteers, teams, assignments and committees.
 *
 * @see docs/02-database-schema.md sections 9 and 10
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volunteer_teams', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 80);
            $table->string('name_bn', 80)->nullable();
            $table->text('description')->nullable();
            $table->text('description_bn')->nullable();
            $table->foreignId('lead_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('volunteers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('crm_contact_id')->nullable()->constrained()->nullOnDelete();
            // Snapshot: survives deletion of the linked member or contact.
            $table->string('name', 150);
            $table->string('phone', 32)->nullable();
            $table->string('email', 191)->nullable();
            $table->json('skills')->nullable();
            $table->string('availability', 120)->nullable();
            $table->string('location', 120)->nullable();
            $table->string('status', 16)->default(VolunteerStatus::Applied->value);
            $table->text('notes')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'volunteers_status_index');
            $table->index('member_id', 'volunteers_member_index');
        });

        Schema::create('volunteer_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('volunteer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('volunteer_team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('responsibility', 200)->nullable();
            $table->timestamp('shift_start')->nullable();
            $table->timestamp('shift_end')->nullable();
            $table->string('status', 16)->default(AssignmentStatus::Assigned->value);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['event_id', 'status'], 'volunteer_assignments_event_status_index');
        });

        Schema::create('committees', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->string('name_bn', 120)->nullable();
            $table->string('type', 32);
            $table->text('description')->nullable();
            $table->text('description_bn')->nullable();
            $table->date('term_start')->nullable();
            $table->date('term_end')->nullable();
            $table->string('status', 16)->default('active');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('type', 'committees_type_index');
            $table->index('status', 'committees_status_index');
        });

        Schema::create('committee_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('committee_id')->constrained()->cascadeOnDelete();
            // Nullable: historic committee members predate the platform and
            // must still be displayable, so name/photo fall back to free text.
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('name_bn', 150)->nullable();
            $table->string('role', 80);
            $table->string('designation', 120)->nullable();
            $table->string('designation_bn', 120)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->text('bio')->nullable();
            $table->text('bio_bn')->nullable();
            $table->string('contact_email', 191)->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 16)->default(CommitteeMemberStatus::Active->value);
            $table->timestamps();

            $table->index(['committee_id', 'display_order'], 'committee_members_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_members');
        Schema::dropIfExists('committees');
        Schema::dropIfExists('volunteer_assignments');
        Schema::dropIfExists('volunteers');
        Schema::dropIfExists('volunteer_teams');
    }
};
