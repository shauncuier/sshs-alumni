<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-cutting tables every other module depends on:
 * settings, media, audit_logs, message_templates, notifications.
 *
 * @see docs/02-database-schema.md section 13
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Settings ─────────────────────────────────────────────────────
        // `organization` (association, est. 2015) and `school` (est. 1976)
        // are separate groups on purpose — the two bodies must never be
        // conflated. See docs/00-overview.md section 3.
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 40);
            $table->string('key', 80);
            $table->json('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->unique(['group', 'key'], 'settings_group_key_unique');
            $table->index('group', 'settings_group_index');
            $table->index('is_public', 'settings_is_public_index');
        });

        // ── Media ────────────────────────────────────────────────────────
        // Polymorphic and optionally unattached, so the admin media library
        // can hold files before they are used anywhere.
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->nullableMorphs('model');
            $table->string('collection', 40);
            $table->string('disk', 20)->default('public');
            $table->string('path', 500);
            $table->string('thumb_path', 500)->nullable();
            $table->string('original_name', 255);
            $table->string('mime_type', 120);
            $table->string('extension', 12);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt', 255)->nullable();
            $table->string('alt_bn', 255)->nullable();
            // false routes the file to the private disk; private files are
            // never served by URL. See docs/08-security-privacy.md section 5.
            $table->boolean('is_public')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('collection', 'media_collection_index');
            $table->index('mime_type', 'media_mime_type_index');
            $table->index('is_public', 'media_is_public_index');
        });

        // ── Audit logs ───────────────────────────────────────────────────
        // Append-only: no updated_at, no soft delete. Stores only the
        // attributes that changed, never the whole row — which is what keeps
        // password hashes and tokens out of the audit trail.
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80);
            $table->nullableMorphs('auditable');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('action', 'audit_logs_action_index');
            $table->index('created_at', 'audit_logs_created_at_index');
            $table->index(
                ['auditable_type', 'auditable_id', 'created_at'],
                'audit_logs_auditable_created_index',
            );
        });

        // ── Message templates ────────────────────────────────────────────
        Schema::create('message_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('name', 120);
            $table->string('channel', 16);
            $table->string('subject', 255)->nullable();
            $table->string('subject_bn', 255)->nullable();
            $table->longText('body');
            $table->longText('body_bn')->nullable();
            $table->json('variables')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index('channel', 'message_templates_channel_index');
        });

        // ── Notifications ────────────────────────────────────────────────
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type', 255);
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('read_at', 'notifications_read_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('media');
        Schema::dropIfExists('settings');
    }
};
