<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The canonical alumni record and its satellites.
 *
 * `members` deliberately holds the core, academic, professional, location and
 * contact fields in one row. The original brief split these across
 * member_profiles / member_contacts; that would turn every directory row and
 * every export into a five-way join. Tables are split out only where the data
 * genuinely repeats (education, employment, links) or is queried on its own
 * (privacy flags, when filtering the directory).
 *
 * @see docs/02-database-schema.md sections 1.1 and 4
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table): void {
            $table->id();
            // Public identifier. Integer ids are never exposed, so
            // /directory/{ulid} cannot be walked by incrementing a number.
            $table->char('ulid', 26)->unique();
            // Nullable both ways: the association enters alumni from paper
            // registers who will never log in, and office staff have logins
            // with no alumni record.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            // Assigned on approval only — never at registration.
            $table->string('membership_no', 32)->nullable()->unique();

            // ── Identity ─────────────────────────────────────────────────
            $table->string('full_name', 150);
            $table->string('full_name_bn', 150)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 16)->nullable();
            $table->string('blood_group', 8)->nullable();

            // ── Relationship to the school ───────────────────────────────
            $table->string('relation_type', 32);

            // ── Academic ─────────────────────────────────────────────────
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->smallInteger('ssc_year')->nullable();
            $table->string('student_id', 32)->nullable();
            $table->smallInteger('admission_year')->nullable();
            $table->string('group_stream', 32)->nullable();
            $table->string('section', 16)->nullable();
            $table->string('house', 32)->nullable();
            $table->string('higher_education', 255)->nullable();

            // ── Professional (current values; history lives in
            //    member_employment and is kept in sync by the observer) ────
            $table->string('occupation', 120)->nullable();
            $table->string('organization', 150)->nullable();
            $table->string('job_title', 120)->nullable();
            $table->string('industry', 80)->nullable();
            $table->text('business_info')->nullable();

            // ── Location ─────────────────────────────────────────────────
            $table->string('country', 80)->nullable()->default('Bangladesh');
            $table->string('division', 80)->nullable();
            $table->string('district', 80)->nullable();
            $table->string('city', 80)->nullable();
            $table->text('address')->nullable();

            // ── Contact ──────────────────────────────────────────────────
            $table->string('mobile', 32)->nullable();
            $table->string('whatsapp', 32)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('emergency_contact_name', 120)->nullable();
            $table->string('emergency_contact_phone', 32)->nullable();

            // ── Profile ──────────────────────────────────────────────────
            $table->text('bio')->nullable();
            $table->text('bio_bn')->nullable();
            $table->json('skills')->nullable();
            $table->json('interests')->nullable();

            // ── Membership state ─────────────────────────────────────────
            $table->string('status', 24)->default(MemberStatus::Pending->value);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('registered_at')->nullable();
            $table->unsignedTinyInteger('profile_completion')->default(0);
            $table->boolean('is_featured')->default(false);

            // Denormalised lowercase name + bn name + organization +
            // occupation + city. One indexed LIKE instead of five OR-ed
            // column scans. Rebuilt by MemberObserver.
            $table->string('search_blob', 500)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'members_status_index');
            $table->index('ssc_year', 'members_ssc_year_index');
            $table->index('blood_group', 'members_blood_group_index');
            $table->index('occupation', 'members_occupation_index');
            $table->index('organization', 'members_organization_index');
            $table->index('industry', 'members_industry_index');
            $table->index('country', 'members_country_index');
            $table->index('division', 'members_division_index');
            $table->index('district', 'members_district_index');
            $table->index('city', 'members_city_index');
            $table->index('mobile', 'members_mobile_index');
            $table->index('email', 'members_email_index');
            $table->index('full_name', 'members_full_name_index');
            $table->index('search_blob', 'members_search_blob_index');
            $table->index('is_featured', 'members_is_featured_index');

            $table->index(['status', 'batch_id'], 'members_status_batch_index');
            $table->index(['status', 'district'], 'members_status_district_index');
            $table->index(['relation_type', 'status'], 'members_relation_status_index');
        });

        // ── Privacy ──────────────────────────────────────────────────────
        // Defaults are privacy-preserving: contact details are hidden unless
        // the member opts in. See docs/08-security-privacy.md section 2.
        Schema::create('member_privacy', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('show_profile')->default(true);
            $table->boolean('show_phone')->default(false);
            $table->boolean('show_email')->default(false);
            $table->boolean('show_workplace')->default(true);
            $table->boolean('show_location')->default(true);
            $table->boolean('show_date_of_birth')->default(false);
            $table->boolean('show_in_batch_list')->default(true);
            $table->timestamps();
        });

        // ── Verification history ─────────────────────────────────────────
        // Append-only. `note` is internal and is never shown to the member;
        // `correction_requested` is the message that is.
        Schema::create('member_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->text('note')->nullable();
            $table->text('correction_requested')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['member_id', 'created_at'], 'member_verifications_member_created_index');
        });

        // ── Social links ─────────────────────────────────────────────────
        Schema::create('member_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('url', 255);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['member_id', 'type', 'url'], 'member_links_unique');
        });

        // ── Education history ────────────────────────────────────────────
        Schema::create('member_education', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('institution', 150);
            $table->string('degree', 120)->nullable();
            $table->string('field_of_study', 120)->nullable();
            $table->smallInteger('start_year')->nullable();
            $table->smallInteger('end_year')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        // ── Employment history ───────────────────────────────────────────
        Schema::create('member_employment', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('organization', 150);
            $table->string('job_title', 120)->nullable();
            $table->string('industry', 80)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['member_id', 'is_current'], 'member_employment_current_index');
        });

        // ── Batch coordinators ───────────────────────────────────────────
        // A genuine many-to-many, unlike batch membership. Grants the Batch
        // Coordinator role its scoped reach, enforced in policies.
        Schema::create('batch_coordinators', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['batch_id', 'member_id'], 'batch_coordinators_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_coordinators');
        Schema::dropIfExists('member_employment');
        Schema::dropIfExists('member_education');
        Schema::dropIfExists('member_links');
        Schema::dropIfExists('member_verifications');
        Schema::dropIfExists('member_privacy');
        Schema::dropIfExists('members');
    }
};
