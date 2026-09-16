<?php

declare(strict_types=1);

use App\Enums\EventDateStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The general-purpose event system. The Golden Jubilee is one row in this
 * table with is_flagship = true — not a separate subsystem that becomes dead
 * code in 2027.
 *
 * THE DATE RULE: `starts_at` is nullable and `date_status` defaults to `tba`.
 * No Golden Jubilee date literal exists anywhere in this codebase. Public
 * surfaces render "তারিখ শীঘ্রই ঘোষণা করা হবে" until an administrator
 * publishes the date.
 *
 * @see docs/17-golden-jubilee.md section 2
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('slug', 120)->unique();
            $table->string('type', 32);
            $table->string('title', 200);
            $table->string('title_bn', 200)->nullable();
            $table->string('summary', 500)->nullable();
            $table->string('summary_bn', 500)->nullable();
            $table->longText('description')->nullable();
            $table->longText('description_bn')->nullable();
            $table->string('cover_path', 255)->nullable();

            // ── The date rule ────────────────────────────────────────────
            $table->string('date_status', 16)->default(EventDateStatus::Tba->value);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->string('venue', 200)->nullable();
            $table->string('venue_bn', 200)->nullable();
            $table->text('address')->nullable();
            $table->string('map_url', 500)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->boolean('registration_required')->default(false);
            $table->timestamp('registration_opens_at')->nullable();
            $table->timestamp('registration_closes_at')->nullable();
            // null means unlimited.
            $table->unsignedInteger('capacity')->nullable();
            $table->decimal('registration_fee', 12, 2)->nullable();
            $table->char('currency', 3)->default('BDT');

            $table->string('organizer_name', 150)->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->string('contact_email', 191)->nullable();

            $table->string('status', 32)->default(EventStatus::Draft->value);
            $table->boolean('is_flagship')->default(false);

            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('og_image_path', 255)->nullable();

            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type', 'events_type_index');
            $table->index('status', 'events_status_index');
            $table->index('date_status', 'events_date_status_index');
            $table->index('starts_at', 'events_starts_at_index');
            $table->index('is_flagship', 'events_is_flagship_index');
            $table->index(['status', 'starts_at'], 'events_status_starts_index');
        });

        Schema::create('event_ticket_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('name_bn', 80)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->char('currency', 3)->default('BDT');
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('sold_count')->default(0);
            $table->unsignedTinyInteger('per_person_limit')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('event_registrations', function (Blueprint $table): void {
            $table->id();
            // The ticket reference, and what the QR code encodes.
            $table->char('ulid', 26)->unique();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('crm_contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ticket_type_id')->nullable()
                ->constrained('event_ticket_types')->nullOnDelete();

            // Snapshot: survives deletion of the member or contact.
            $table->string('registrant_name', 150);
            $table->string('registrant_email', 191)->nullable();
            $table->string('registrant_phone', 32)->nullable();

            $table->unsignedTinyInteger('guests_count')->default(0);
            $table->decimal('amount_due', 12, 2)->default(0);
            $table->char('currency', 3)->default('BDT');
            $table->string('payment_status', 16)->default(PaymentStatus::Pending->value);
            $table->string('status', 16)->default(RegistrationStatus::Confirmed->value);
            // Random, not derived from the id.
            $table->char('qr_token', 40)->unique();
            $table->timestamp('registered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('payment_status', 'event_registrations_payment_status_index');
            $table->index(['event_id', 'status'], 'event_registrations_event_status_index');
            // Prevents a member registering twice for the same event.
            $table->unique(['event_id', 'member_id'], 'event_registrations_event_member_unique');
        });

        Schema::create('event_registration_guests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_registration_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('relation', 60)->nullable();
            $table->string('age_group', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Check-in ─────────────────────────────────────────────────────
        // The UNIQUE constraint on event_registration_id IS the duplicate
        // check-in prevention required by the brief. It is not an
        // application-level if. Attendance is this table — there is no
        // separate event_attendance, which would always hold the same rows.
        Schema::create('event_checkins', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_registration_id')->unique()
                ->constrained()->cascadeOnDelete();
            // Denormalised for per-event attendance queries.
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->timestamp('checked_in_at');
            // Required: accountability when a dispute arises at the gate.
            $table->foreignId('operator_id')->constrained('users')->restrictOnDelete();
            $table->string('gate', 60)->nullable();
            $table->string('device', 120)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('checked_in_at', 'event_checkins_checked_in_at_index');
            $table->index(['event_id', 'checked_in_at'], 'event_checkins_event_time_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_checkins');
        Schema::dropIfExists('event_registration_guests');
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('event_ticket_types');
        Schema::dropIfExists('events');
    }
};
